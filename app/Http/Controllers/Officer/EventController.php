<?php

namespace App\Http\Controllers\Officer;

use App\Http\Controllers\Controller;
use App\Models\ClubOfficer;
use App\Models\Event;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class EventController extends Controller
{
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
            $monthEvents = Event::where('club_id', $club->id)
                ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                ->orderBy('date')
                ->get()
                ->groupBy(fn ($e) => $e->date->format('Y-m-d'));

            $upcomingGrouped = Event::where('club_id', $club->id)
                ->where('date', '>=', today())
                ->orderBy('date')
                ->get()
                ->groupBy(fn ($e) => $e->date->format('F Y'));
        }

        $prevMonth = $startOfMonth->copy()->subMonth();
        $nextMonth = $startOfMonth->copy()->addMonth();

        return view('officer.events.index', compact(
            'club', 'month', 'year', 'startOfMonth',
            'monthEvents', 'upcomingGrouped',
            'prevMonth', 'nextMonth',
        ));
    }

    public function create(): View
    {
        $club = $this->officerClub();

        $venues = [
            'SCC Gymnasium',
            'Main AV Room',
            'Open Court',
            'Function Hall',
            'Covered Court',
            'Others',
        ];

        return view('officer.events.create', compact('club', 'venues'));
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
            'event_type'            => ['required', 'in:standard,acle'],
        ], [
            'title.required'                  => 'Event Title is required.',
            'description.required'            => 'Description is required.',
            'date.required'                   => 'Date is required.',
            'date.after_or_equal'             => 'The event date must be today or in the future.',
            'time_start.required'             => 'Start time is required.',
            'time_end.required'               => 'End time is required.',
            'time_end.after'                  => 'End time must be after start time.',
            'venue.required'                  => 'Location is required.',
            'purpose.required'                => 'Purpose / Objectives is required.',
            'expected_participants.required'  => 'Expected Participants is required.',
            'event_type.required'             => 'Event Type is required.',
        ]);

        // Duplicate title+date check (non-blocking — user can bypass with confirmed flag)
        if (! $request->boolean('confirmed')) {
            $duplicate = Event::where('club_id', $club->id)
                ->where('title', $validated['title'])
                ->whereDate('date', $validated['date'])
                ->exists();

            if ($duplicate) {
                return back()->withInput()->with('duplicate_warning', true);
            }
        }

        $event = Event::create([
            'club_id'               => $club->id,
            'title'                 => $validated['title'],
            'description'           => $validated['description'],
            'date'                  => $validated['date'],
            'time_start'            => $validated['time_start'],
            'time_end'              => $validated['time_end'],
            'venue'                 => $validated['venue'],
            'purpose'               => $validated['purpose'],
            'expected_participants' => $validated['expected_participants'],
            'event_type'            => $validated['event_type'],
            'status'                => 'scheduled',
            'created_by'            => auth_user_id(),
        ]);

        // Mirror to Firebase RTDB (eventual consistency — do not roll back on failure)
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
            Log::warning('Firebase event mirror failed for event #' . $event->id . ': ' . $e->getMessage());
        }

        return redirect()->route('officer.events.index')
            ->with('success', 'Event successfully scheduled!');
    }

    public function show(Event $event): View
    {
        // Verify the event belongs to one of the officer's clubs
        $club = $this->officerClub();

        if (! $club || $event->club_id !== $club->id) {
            abort(403);
        }

        $event->load('club', 'creator');

        $attendanceCount = $event->attendance()->whereNotNull('time_in')->count();

        return view('officer.events.show', compact('event', 'club', 'attendanceCount'));
    }

    public function complete(Request $request, Event $event): RedirectResponse
    {
        // Stub — full implementation in Step 7 (Post-Event Report)
        abort(501, 'Post-event report automation is not yet implemented.');
    }
}
