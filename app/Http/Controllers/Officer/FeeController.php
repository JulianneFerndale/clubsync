<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\ClubMember;
use App\Models\ClubOfficer;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FeeController extends Controller
{
    private function officerClub(): ?\App\Models\Club
    {
        return ClubOfficer::where('user_id', auth_user_id())
            ->with('club')
            ->first()
            ?->club;
    }

    public function index(): View
    {
        $club = $this->officerClub();

        $fees = $club
            ? Fee::where('club_id', $club->id)
                ->withCount(['payments as paid_count'   => fn ($q) => $q->where('status', 'paid')])
                ->withCount(['payments as total_count'])
                ->orderByDesc('created_at')
                ->get()
            : collect();

        return view('officer.fees.index', compact('club', 'fees'));
    }

    public function create(): View
    {
        $club = $this->officerClub();

        return view('officer.fees.create', compact('club'));
    }

    public function store(Request $request): RedirectResponse
    {
        $club = $this->officerClub();

        if (! $club) {
            return back()->withErrors(['club' => 'You are not assigned to any club.']);
        }

        $validated = $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'due_date'        => ['required', 'date', 'after_or_equal:today'],
            'academic_period' => ['required', 'string', 'max:50'],
        ], [
            'title.required'           => 'Fee title is required.',
            'amount.required'          => 'Amount is required.',
            'amount.min'               => 'Amount must be greater than zero.',
            'due_date.required'        => 'Due date is required.',
            'due_date.after_or_equal'  => 'Due date must be today or in the future.',
            'academic_period.required' => 'Academic period is required.',
        ]);

        // Wrap the fee + its per-member payment rows in a transaction so that a
        // cancelled/aborted request (e.g. the user hits "Stop") never leaves a
        // fee with only some members assigned — it all commits or none of it does.
        $memberIds = ClubMember::where('club_id', $club->id)
            ->where('status', 'active')
            ->pluck('user_id');

        $fee = DB::transaction(function () use ($club, $validated, $memberIds) {
            $fee = Fee::create([
                'club_id'         => $club->id,
                'title'           => $validated['title'],
                'amount'          => $validated['amount'],
                'due_date'        => $validated['due_date'],
                'academic_period' => $validated['academic_period'],
            ]);

            // Auto-create pending payment records for every active club member
            foreach ($memberIds as $userId) {
                FeePayment::firstOrCreate(
                    ['fee_id' => $fee->id, 'user_id' => $userId],
                    ['status' => 'pending'],
                );
            }

            return $fee;
        });

        return redirect()->route('officer.fees.show', $fee)
            ->with('success', 'Fee created and assigned to ' . $memberIds->count() . ' active members.');
    }

    public function show(Fee $fee): View
    {
        $club = $this->officerClub();

        if (! $club || $fee->club_id !== $club->id) {
            abort(403);
        }

        $payments = FeePayment::where('fee_id', $fee->id)
            ->with('user')
            ->get()
            ->sortBy(fn ($p) => $p->user?->last_name . $p->user?->first_name);

        $paidCount    = $payments->where('status', 'paid')->count();
        $pendingCount = $payments->where('status', 'pending')->count();

        return view('officer.fees.show', compact('club', 'fee', 'payments', 'paidCount', 'pendingCount'));
    }

    public function edit(Fee $fee): View
    {
        $club = $this->officerClub();

        if (! $club || $fee->club_id !== $club->id) {
            abort(403);
        }

        return view('officer.fees.edit', compact('club', 'fee'));
    }

    public function update(Request $request, Fee $fee): RedirectResponse
    {
        $club = $this->officerClub();

        if (! $club || $fee->club_id !== $club->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'due_date'        => ['required', 'date'],
            'academic_period' => ['required', 'string', 'max:50'],
        ], [
            'title.required'           => 'Fee title is required.',
            'amount.required'          => 'Amount is required.',
            'amount.min'               => 'Amount must be greater than zero.',
            'due_date.required'        => 'Due date is required.',
            'academic_period.required' => 'Academic period is required.',
        ]);

        $fee->update($validated);

        return redirect()->route('officer.fees.show', $fee)->with('success', 'Fee updated.');
    }

    public function markPaid(Fee $fee, User $user): RedirectResponse
    {
        $club = $this->officerClub();

        if (! $club || $fee->club_id !== $club->id) {
            abort(403);
        }

        FeePayment::where('fee_id', $fee->id)
            ->where('user_id', $user->id)
            ->update([
                'status'       => 'paid',
                'confirmed_by' => auth_user_id(),
                'confirmed_at' => now(),
            ]);

        return back();
    }

    public function markUnpaid(Fee $fee, User $user): RedirectResponse
    {
        $club = $this->officerClub();

        if (! $club || $fee->club_id !== $club->id) {
            abort(403);
        }

        FeePayment::where('fee_id', $fee->id)
            ->where('user_id', $user->id)
            ->update([
                'status'       => 'pending',
                'confirmed_by' => null,
                'confirmed_at' => null,
            ]);

        return back();
    }
}
