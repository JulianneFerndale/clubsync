<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateChedReportJob;
use App\Jobs\GenerateClubNarrativeJob;
use App\Jobs\NotifyDsaOfActivitySubmission;
use App\Models\ActivityChangeLog;
use App\Models\ClubActivity;
use App\Models\ClubOfficer;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ActivityController extends Controller
{
    private const VENUES = [
        'SCC Gymnasium',
        'Main AV Room',
        'Open Court',
        'Function Hall',
        'Covered Court',
        'Others',
    ];

    public function __construct(private FirebaseService $firebase) {}

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function officerClub(): ?\App\Models\Club
    {
        return ClubOfficer::where('user_id', auth_user_id())
            ->with('club')
            ->first()
            ?->club;
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $club = $this->officerClub();

        $month = $request->integer('month', now()->month);
        $year  = $request->integer('year',  now()->year);

        // Clamp to valid range
        $month = max(1, min(12, $month));

        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth   = $startOfMonth->copy()->endOfMonth();

        $monthEvents = collect();
        $upcomingGrouped = collect();

        if ($club) {
            $monthEvents = ClubActivity::where('club_id', $club->id)
                ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                ->orderBy('date')
                ->get()
                ->groupBy(fn ($e) => $e->date->format('Y-m-d'));

            $upcomingGrouped = ClubActivity::where('club_id', $club->id)
                ->where('date', '>=', today())
                ->orderBy('date')
                ->get()
                ->groupBy(fn ($e) => $e->date->format('F Y'));
        }

        $prevMonth = $startOfMonth->copy()->subMonth();
        $nextMonth = $startOfMonth->copy()->addMonth();

        return view('officer.activities.index', compact(
            'club', 'month', 'year', 'startOfMonth',
            'monthEvents', 'upcomingGrouped',
            'prevMonth', 'nextMonth',
        ));
    }

    public function create(): View
    {
        $club  = $this->officerClub();
        $venues = self::VENUES;

        return view('officer.activities.create', compact('club', 'venues'));
    }

    public function store(Request $request): RedirectResponse
    {
        $club = $this->officerClub();

        if (! $club) {
            return back()->withErrors(['club' => 'You are not assigned to any club.']);
        }

        $validated = $request->validate([
            'title'                 => ['required', 'string', 'max:255'],
            'description'           => ['required', 'string'],
            'date'                  => ['required', 'date', 'after_or_equal:today'],
            'time_start'            => ['required', 'date_format:H:i'],
            'time_end'              => ['required', 'date_format:H:i', 'after:time_start'],
            'venue'                 => ['required', 'string', 'max:255'],
            'purpose'               => ['required', 'string'],
            'expected_participants' => ['required', 'integer', 'min:1'],
            'activity_type'         => ['required', 'in:internal_meeting,acle,community_involvement,campus_resource_use,other_external'],
            'approval_letter'       => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'title.required'                  => 'Activity Title is required.',
            'description.required'            => 'Description is required.',
            'date.required'                   => 'Date is required.',
            'date.after_or_equal'             => 'The activity date must be today or in the future.',
            'time_start.required'             => 'Start time is required.',
            'time_end.required'               => 'End time is required.',
            'time_end.after'                  => 'End time must be after start time.',
            'venue.required'                  => 'Location is required.',
            'purpose.required'                => 'Purpose / Objectives is required.',
            'expected_participants.required'  => 'Expected Participants is required.',
            'activity_type.required'          => 'Activity Type is required.',
        ]);

        // Duplicate title+date check (non-blocking — user can bypass with confirmed flag)
        if (! $request->boolean('confirmed')) {
            $duplicate = ClubActivity::where('club_id', $club->id)
                ->where('title', $validated['title'])
                ->whereDate('date', $validated['date'])
                ->exists();

            if ($duplicate) {
                return back()->withInput()->with('duplicate_warning', true);
            }
        }

        // Internal meetings are auto-approved; everything else requires DSA sign-off.
        $needsApproval = $validated['activity_type'] !== 'internal_meeting';

        $approvalLetterPath = null;
        if ($request->hasFile('approval_letter')) {
            $approvalLetterPath = $request->file('approval_letter')->store('approval-letters', 'local');
        }

        $event = ClubActivity::create([
            'club_id'               => $club->id,
            'title'                 => $validated['title'],
            'description'           => $validated['description'],
            'date'                  => $validated['date'],
            'time_start'            => $validated['time_start'],
            'time_end'              => $validated['time_end'],
            'venue'                 => $validated['venue'],
            'purpose'               => $validated['purpose'],
            'expected_participants' => $validated['expected_participants'],
            'activity_type'         => $validated['activity_type'],
            'approval_status'       => $needsApproval ? 'pending_approval' : 'no_approval_needed',
            'approval_letter_path'  => $approvalLetterPath,
            'status'                => 'scheduled',
            'created_by'            => auth_user_id(),
        ]);

        if ($needsApproval) {
            NotifyDsaOfActivitySubmission::dispatch($event->id);
        }

        // Mirror to Firebase RTDB (eventual consistency — do not roll back on failure)
        // TODO: remove SQLite guard / revisit Firebase node naming before Supabase migration
        try {
            $this->firebase->writeEvent($club->id, $event->id, [
                'title'      => $event->title,
                'date'       => $event->date->toDateString(),
                'time_start' => $event->time_start,
                'time_end'   => $event->time_end,
                'venue'      => $event->venue,
                'status'     => $event->status,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Firebase activity mirror failed for activity #' . $event->id . ': ' . $e->getMessage());
        }

        return redirect()->route('officer.activities.index')
            ->with('success', 'Activity successfully scheduled!');
    }

    public function show(ClubActivity $event): View
    {
        // Verify the activity belongs to one of the officer's clubs
        $club = $this->officerClub();

        if (! $club || $event->club_id !== $club->id) {
            abort(403);
        }

        $event->load('club', 'creator', 'changeLogs.changedBy', 'chedReport');

        $attendanceCount = $event->attendance()->whereNotNull('time_in')->count();

        return view('officer.activities.show', compact('event', 'club', 'attendanceCount'));
    }

    public function edit(ClubActivity $event): View
    {
        $club = $this->officerClub();

        if (! $club || $event->club_id !== $club->id) {
            abort(403);
        }

        $venues = self::VENUES;

        return view('officer.activities.edit', compact('event', 'club', 'venues'));
    }

    public function update(Request $request, ClubActivity $event): RedirectResponse
    {
        $club = $this->officerClub();

        if (! $club || $event->club_id !== $club->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title'                 => ['required', 'string', 'max:255'],
            'description'           => ['required', 'string'],
            'date'                  => ['required', 'date'],
            'time_start'            => ['required', 'date_format:H:i'],
            'time_end'              => ['required', 'date_format:H:i', 'after:time_start'],
            'venue'                 => ['required', 'string', 'max:255'],
            'purpose'               => ['required', 'string'],
            'expected_participants' => ['required', 'integer', 'min:1'],
            'activity_type'         => ['required', 'in:internal_meeting,acle,community_involvement,campus_resource_use,other_external'],
            'approval_letter'       => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'title.required'                  => 'Activity Title is required.',
            'description.required'            => 'Description is required.',
            'date.required'                   => 'Date is required.',
            'time_start.required'             => 'Start time is required.',
            'time_end.required'               => 'End time is required.',
            'time_end.after'                  => 'End time must be after start time.',
            'venue.required'                  => 'Location is required.',
            'purpose.required'                => 'Purpose / Objectives is required.',
            'expected_participants.required'  => 'Expected Participants is required.',
            'activity_type.required'          => 'Activity Type is required.',
        ]);

        $before = $event->only([
            'title', 'description', 'date', 'time_start', 'time_end',
            'venue', 'purpose', 'expected_participants', 'activity_type', 'approval_status',
        ]);
        $before['date'] = $event->date->toDateString();

        $wasApprovedAndNowNeedsReapproval = $event->approval_status === 'approved'
            && $validated['activity_type'] !== 'internal_meeting';

        if ($request->hasFile('approval_letter')) {
            $validated['approval_letter_path'] = $request->file('approval_letter')->store('approval-letters', 'local');
        }

        if ($validated['activity_type'] === 'internal_meeting') {
            $validated['approval_status'] = 'no_approval_needed';
        } elseif ($wasApprovedAndNowNeedsReapproval) {
            $validated['approval_status'] = 'pending_approval';
            $validated['dsa_remarks']     = null;
        }

        $event->update($validated);

        $after = $event->only([
            'title', 'description', 'date', 'time_start', 'time_end',
            'venue', 'purpose', 'expected_participants', 'activity_type', 'approval_status',
        ]);
        $after['date'] = $event->date->toDateString();

        $changes = array_filter(
            array_map(
                fn ($key) => $before[$key] != $after[$key] ? ['field' => $key, 'before' => $before[$key], 'after' => $after[$key]] : null,
                array_keys($before)
            )
        );

        if (! empty($changes)) {
            ActivityChangeLog::create([
                'club_activity_id' => $event->id,
                'changed_by'       => auth_user_id(),
                'changes'          => array_values($changes),
            ]);
        }

        if ($wasApprovedAndNowNeedsReapproval) {
            NotifyDsaOfActivitySubmission::dispatch($event->id);
        }

        return redirect()->route('officer.activities.show', $event)
            ->with('success', 'Activity updated.' . ($wasApprovedAndNowNeedsReapproval ? ' DSA approval is required again before this activity is confirmed.' : ''));
    }

    public function downloadLetter(ClubActivity $event)
    {
        $club = $this->officerClub();

        if (! $club || $event->club_id !== $club->id || ! $event->approval_letter_path) {
            abort(404);
        }

        return Storage::disk('local')->download($event->approval_letter_path);
    }

    public function complete(Request $request, ClubActivity $event): RedirectResponse
    {
        $club = $this->officerClub();

        if (! $club || $event->club_id !== $club->id) {
            abort(403);
        }

        if ($event->status !== 'scheduled') {
            return back()->withErrors(['status' => 'This activity has already been completed.']);
        }

        $needsChedReport = in_array($event->activity_type, ['acle', 'community_involvement'], true);

        $event->update([
            'status'             => 'completed',
            'completed_at'       => now(),
            'post_report_status' => $needsChedReport ? 'pending' : null,
        ]);

        if ($needsChedReport) {
            GenerateChedReportJob::dispatch($event->id);
        }

        // Milestone reached — draft a post-event narrative for the bulletin.
        // The draft is held for adviser review; nothing is published automatically.
        GenerateClubNarrativeJob::dispatch($event->id);

        return redirect()->route('officer.activities.show', $event)
            ->with('success', 'Activity marked as completed.' . ($needsChedReport ? ' A CHED report is being generated.' : ''));
    }
}
