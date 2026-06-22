<?php

namespace App\Http\Controllers\DSA;

use App\Http\Controllers\Controller;
use App\Jobs\NotifyClubOfMemberDecision;
use App\Models\Club;
use App\Models\ClubMember;
use App\Services\FirebaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ClubMemberController extends Controller
{
    public function __construct(private FirebaseService $firebase) {}

    public function index(Club $club): View
    {
        $members = ClubMember::where('club_id', $club->id)
            ->with('user', 'submittedBy', 'approvedBy')
            ->orderByDesc('created_at')
            ->get();

        return view('dsa.members.review', compact('club', 'members'));
    }

    public function approve(Club $club, ClubMember $member): RedirectResponse
    {
        if ($member->club_id !== $club->id) {
            abort(403);
        }

        $member->update([
            'registration_status' => 'approved',
            'status'               => 'active',
            'approved_by'          => auth_user_id(),
            'approved_at'          => now(),
            'dsa_remarks'          => null,
        ]);

        $this->mirrorAndNotify($club, $member, 'approved');

        return back()->with('success', 'Member registration approved.');
    }

    public function reject(Request $request, Club $club, ClubMember $member): RedirectResponse
    {
        if ($member->club_id !== $club->id) {
            abort(403);
        }

        $validated = $request->validate([
            'dsa_remarks' => ['required', 'string', 'min:5'],
        ], [
            'dsa_remarks.required' => 'Please provide a reason for rejection.',
        ]);

        $member->update([
            'registration_status' => 'rejected',
            'dsa_remarks'          => $validated['dsa_remarks'],
            'approved_by'          => auth_user_id(),
            'approved_at'          => null,
        ]);

        $this->mirrorAndNotify($club, $member, 'rejected');

        return back()->with('success', 'Member registration rejected.');
    }

    private function mirrorAndNotify(Club $club, ClubMember $member, string $decision): void
    {
        try {
            $this->firebase->writeMemberStatus($club->id, $member->id, [
                'registration_status' => $member->registration_status,
                'decided_at'           => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Firebase member_status mirror failed for member #' . $member->id . ': ' . $e->getMessage());
        }

        NotifyClubOfMemberDecision::dispatch($member->id, $decision);
    }
}
