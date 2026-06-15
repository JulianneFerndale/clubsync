<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ClubOfficer;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function __construct(private GeminiService $gemini) {}

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

        $announcements = $club
            ? Announcement::where('club_id', $club->id)
                ->where('author_id', auth_user_id())
                ->orderByDesc('created_at')
                ->get()
            : collect();

        return view('officer.announcements.index', compact('club', 'announcements'));
    }

    public function create(): View
    {
        $club        = $this->officerClub();
        $aiAvailable = $this->gemini->isAvailable();

        return view('officer.announcements.create', compact('club', 'aiAvailable'));
    }

    public function store(Request $request): RedirectResponse
    {
        $club = $this->officerClub();

        if (! $club) {
            return back()->withErrors(['club' => 'You are not assigned to any club.']);
        }

        $validated = $request->validate([
            'title'   => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'min:10'],
            'type'    => ['required', 'in:announcement,letter'],
        ], [
            'content.required' => 'Content is required.',
            'content.min'      => 'Content must be at least 10 characters.',
            'type.required'    => 'Please select a type.',
        ]);

        $announcement = Announcement::create([
            'club_id'      => $club->id,
            'author_id'    => auth_user_id(),
            'title'        => $validated['title'] ?? null,
            'content'      => $validated['content'],
            'type'         => $validated['type'],
            'status'       => 'draft',
            'ai_assisted'  => $request->boolean('ai_assisted'),
        ]);

        return redirect()->route('officer.announcements.show', $announcement)
            ->with('success', 'Draft saved.');
    }

    public function show(Announcement $announcement): View
    {
        $club = $this->officerClub();

        if (! $club || $announcement->club_id !== $club->id || $announcement->author_id !== auth_user_id()) {
            abort(403);
        }

        return view('officer.announcements.show', compact('announcement', 'club'));
    }

    public function edit(Announcement $announcement): View
    {
        $club = $this->officerClub();

        if (! $club || $announcement->club_id !== $club->id || $announcement->author_id !== auth_user_id()) {
            abort(403);
        }

        if (! in_array($announcement->status, ['draft', 'revision_required'])) {
            abort(403, 'This announcement can no longer be edited.');
        }

        $aiAvailable = $this->gemini->isAvailable();

        return view('officer.announcements.edit', compact('announcement', 'club', 'aiAvailable'));
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $club = $this->officerClub();

        if (! $club || $announcement->club_id !== $club->id || $announcement->author_id !== auth_user_id()) {
            abort(403);
        }

        if (! in_array($announcement->status, ['draft', 'revision_required'])) {
            abort(403);
        }

        $validated = $request->validate([
            'title'   => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'min:10'],
            'type'    => ['required', 'in:announcement,letter'],
        ]);

        $announcement->update([
            'title'       => $validated['title'] ?? null,
            'content'     => $validated['content'],
            'type'        => $validated['type'],
            'ai_assisted' => $announcement->ai_assisted || $request->boolean('ai_assisted'),
        ]);

        return redirect()->route('officer.announcements.show', $announcement)
            ->with('success', 'Draft updated.');
    }

    public function submit(Announcement $announcement): RedirectResponse
    {
        $club = $this->officerClub();

        if (! $club || $announcement->club_id !== $club->id || $announcement->author_id !== auth_user_id()) {
            abort(403);
        }

        if (! in_array($announcement->status, ['draft', 'revision_required'])) {
            return back()->withErrors(['status' => 'This announcement cannot be submitted.']);
        }

        $announcement->update(['status' => 'pending_review']);

        return redirect()->route('officer.announcements.show', $announcement)
            ->with('success', 'Submitted for adviser review.');
    }

    // AJAX endpoint — returns generated draft content as JSON
    public function aiDraft(Request $request): JsonResponse
    {
        $club = $this->officerClub();

        if (! $club) {
            return response()->json(['error' => 'No club assigned.'], 403);
        }

        if (! $this->gemini->isAvailable()) {
            return response()->json(['error' => 'AI is not configured.'], 503);
        }

        $request->validate([
            'title'   => ['required', 'string', 'max:255'],
            'type'    => ['required', 'in:announcement,letter'],
            'context' => ['nullable', 'string', 'max:500'],
        ]);

        $content = $this->gemini->draftAnnouncement(
            $request->input('title'),
            $request->input('type', 'announcement'),
            $club->name,
            $request->input('context', ''),
        );

        if ($content === null) {
            return response()->json(['error' => 'AI generation failed. Please try again.'], 500);
        }

        return response()->json(['content' => trim($content)]);
    }
}
