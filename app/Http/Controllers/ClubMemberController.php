<?php

namespace App\Http\Controllers;

use App\Jobs\NotifyDsaOfMemberSubmission;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\ClubOfficer;
use App\Services\FirebaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ClubMemberController extends Controller
{
    public function __construct(private FirebaseService $firebase) {}

    private function currentClub(): ?Club
    {
        if (auth_role() === 'adviser') {
            return Club::where('adviser_id', auth_user_id())->first();
        }

        return ClubOfficer::where('user_id', auth_user_id())->with('club')->first()?->club;
    }

    public function index(): View
    {
        $club = $this->currentClub();

        $members = $club
            ? ClubMember::where('club_id', $club->id)
                ->with('user', 'submittedBy', 'approvedBy')
                ->orderByDesc('created_at')
                ->get()
            : collect();

        return view('clubs.members.index', compact('club', 'members'));
    }

    public function store(Request $request): RedirectResponse
    {
        $club = $this->currentClub();

        if (! $club) {
            return back()->withErrors(['club' => 'You are not associated with any club.']);
        }

        $validated = $request->validate([
            'member_ids'   => ['required', 'array', 'min:1'],
            'member_ids.*' => ['integer', 'exists:club_members,id'],
        ]);

        $members = ClubMember::where('club_id', $club->id)
            ->whereIn('id', $validated['member_ids'])
            ->where('registration_status', '!=', 'approved') // locked once approved
            ->get();

        foreach ($members as $member) {
            $member->update([
                'submitted_by'        => auth_user_id(),
                'registration_status' => 'pending',
                'dsa_remarks'         => null,
                'approved_by'         => null,
                'approved_at'         => null,
            ]);
        }

        if ($members->isEmpty()) {
            return back()->with('info', 'No eligible members were selected for submission.');
        }

        NotifyDsaOfMemberSubmission::dispatch($club->id, $members->count(), auth_user_id());

        return redirect()->route('clubs.members.index')
            ->with('success', $members->count() . ' member(s) submitted to the DSA for review.');
    }
}
