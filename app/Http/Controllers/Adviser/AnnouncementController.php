<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Club;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    private function adviserClub(): ?Club
    {
        return Club::where('adviser_id', auth_user_id())->first();
    }

    public function index(): View
    {
        $club = $this->adviserClub();

        $pending = $club
            ? Announcement::where('club_id', $club->id)
                ->pendingReview()
                ->with('author')
                ->orderBy('created_at')
                ->get()
            : collect();

        $reviewed = $club
            ? Announcement::where('club_id', $club->id)
                ->whereIn('status', ['published', 'revision_required', 'rejected'])
                ->with('author')
                ->orderByDesc('reviewed_at')
                ->take(20)
                ->get()
            : collect();

        return view('adviser.announcements.index', compact('club', 'pending', 'reviewed'));
    }

    public function show(Announcement $announcement): View
    {
        $club = $this->adviserClub();

        if (! $club || $announcement->club_id !== $club->id) {
            abort(403);
        }

        $announcement->load('author');

        return view('adviser.announcements.show', compact('announcement', 'club'));
    }

    public function approve(Announcement $announcement): RedirectResponse
    {
        $club = $this->adviserClub();

        if (! $club || $announcement->club_id !== $club->id) {
            abort(403);
        }

        $announcement->update([
            'status'      => 'published',
            'reviewed_by' => auth_user_id(),
            'reviewed_at' => now(),
            'published_at' => now(),
        ]);

        return redirect()->route('adviser.announcements.index')
            ->with('success', 'Announcement approved and published.');
    }

    public function requestRevision(Request $request, Announcement $announcement): RedirectResponse
    {
        $club = $this->adviserClub();

        if (! $club || $announcement->club_id !== $club->id) {
            abort(403);
        }

        $request->validate([
            'adviser_notes' => ['required', 'string', 'min:5'],
        ], [
            'adviser_notes.required' => 'Please provide revision notes for the officer.',
        ]);

        $announcement->update([
            'status'        => 'revision_required',
            'adviser_notes' => $request->input('adviser_notes'),
            'reviewed_by'   => auth_user_id(),
            'reviewed_at'   => now(),
        ]);

        return redirect()->route('adviser.announcements.index')
            ->with('success', 'Revision requested. The officer will be notified.');
    }

    public function reject(Request $request, Announcement $announcement): RedirectResponse
    {
        $club = $this->adviserClub();

        if (! $club || $announcement->club_id !== $club->id) {
            abort(403);
        }

        $request->validate([
            'adviser_notes' => ['required', 'string', 'min:5'],
        ], [
            'adviser_notes.required' => 'Please provide a reason for rejection.',
        ]);

        $announcement->update([
            'status'        => 'rejected',
            'adviser_notes' => $request->input('adviser_notes'),
            'reviewed_by'   => auth_user_id(),
            'reviewed_at'   => now(),
        ]);

        return redirect()->route('adviser.announcements.index')
            ->with('success', 'Announcement rejected.');
    }
}
