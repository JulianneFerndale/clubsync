<?php

namespace App\Http\Controllers\DSA;

use App\Http\Controllers\Controller;
use App\Jobs\NotifyOfficersOfActivityDecision;
use App\Jobs\SyncActivityToGoogleCalendar;
use App\Models\Club;
use App\Models\ClubActivity;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function monitor(Request $request): View
    {
        $query = ClubActivity::with('club')->orderBy('date');

        if ($request->filled('club_id')) {
            $query->where('club_id', $request->input('club_id'));
        }

        if ($request->filled('activity_type')) {
            $query->where('activity_type', $request->input('activity_type'));
        }

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->input('approval_status'));
        }

        if ($request->filled('semester_id')) {
            $semester = Semester::find($request->input('semester_id'));

            if ($semester) {
                $query->whereBetween('date', [$semester->start_date, $semester->end_date]);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->input('date_to'));
        }

        $activities = $query->paginate(20)->withQueryString();

        $clubs     = Club::orderBy('name')->get();
        $semesters = Semester::orderByDesc('start_date')->get();

        return view('dsa.activities.monitor', compact('activities', 'clubs', 'semesters'));
    }

    public function show(ClubActivity $event): View
    {
        $event->load('club', 'creator', 'approvedBy', 'changeLogs.changedBy');

        return view('dsa.activities.show', compact('event'));
    }

    public function review(): View
    {
        $pending = ClubActivity::where('approval_status', 'pending_approval')
            ->with('club')
            ->orderBy('date')
            ->get();

        $reviewed = ClubActivity::whereIn('approval_status', ['approved', 'rejected'])
            ->with('club')
            ->orderByDesc('approved_at')
            ->take(20)
            ->get();

        return view('dsa.activities.review', compact('pending', 'reviewed'));
    }

    public function approve(ClubActivity $event): RedirectResponse
    {
        $event->update([
            'approval_status' => 'approved',
            'approved_by'      => auth_user_id(),
            'approved_at'      => now(),
            'dsa_remarks'      => null,
        ]);

        NotifyOfficersOfActivityDecision::dispatch($event->id, 'approved');
        SyncActivityToGoogleCalendar::dispatch($event->id); // add to institutional Google Calendar

        return back()->with('success', 'Activity approved.');
    }

    public function reject(Request $request, ClubActivity $event): RedirectResponse
    {
        $validated = $request->validate([
            'dsa_remarks' => ['required', 'string', 'min:5'],
        ], [
            'dsa_remarks.required' => 'Please provide a reason for rejection.',
        ]);

        $event->update([
            'approval_status' => 'rejected',
            'dsa_remarks'      => $validated['dsa_remarks'],
            'approved_by'      => auth_user_id(),
            'approved_at'      => null,
        ]);

        NotifyOfficersOfActivityDecision::dispatch($event->id, 'rejected');
        SyncActivityToGoogleCalendar::dispatch($event->id); // removes it from the calendar if it was there

        return back()->with('success', 'Activity rejected.');
    }

    public function downloadLetter(ClubActivity $event)
    {
        if (! $event->approval_letter_path) {
            abort(404);
        }

        return Storage::disk('local')->download($event->approval_letter_path);
    }
}
