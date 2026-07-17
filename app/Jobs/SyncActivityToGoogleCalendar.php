<?php

namespace App\Jobs;

use App\Models\ClubActivity;
use App\Services\GoogleCalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Background-sync a confirmed club activity to the institutional Google Calendar.
 * Queued so it runs even while users are offline. Idempotent: creates, updates,
 * or removes the mirrored event to match the activity's current state.
 */
class SyncActivityToGoogleCalendar implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** approval_status values that mean the activity is confirmed and belongs on the calendar. */
    private const CONFIRMED = ['no_approval_needed', 'approved'];

    public function __construct(private int $activityId) {}

    public function handle(GoogleCalendarService $calendar): void
    {
        if (! $calendar->enabled()) {
            return;
        }

        $activity = ClubActivity::with('club')->find($this->activityId);
        if (! $activity) {
            return;
        }

        $confirmed = in_array($activity->approval_status, self::CONFIRMED, true)
            && $activity->status !== 'cancelled';

        // Not confirmed (or cancelled): remove any mirrored event.
        if (! $confirmed) {
            if ($activity->google_event_id) {
                $calendar->deleteEvent($activity->google_event_id);
                $activity->forceFill(['google_event_id' => null])->saveQuietly();
            }

            return;
        }

        $payload = $this->payload($activity);

        if ($activity->google_event_id) {
            if ($calendar->updateEvent($activity->google_event_id, $payload)) {
                return;
            }
            // The event was removed upstream — fall through and recreate it.
        }

        $eventId = $calendar->createEvent($payload);
        if ($eventId) {
            $activity->forceFill(['google_event_id' => $eventId])->saveQuietly();
        }
    }

    private function payload(ClubActivity $activity): array
    {
        $tz    = config('google.calendar.timezone', 'Asia/Manila');
        $date  = $activity->date->toDateString();
        $start = Carbon::parse($date . ' ' . $activity->time_start, $tz);
        $end   = Carbon::parse($date . ' ' . ($activity->time_end ?: $activity->time_start), $tz);

        if ($end->lessThanOrEqualTo($start)) {
            $end = $start->copy()->addHour();
        }

        $descriptionParts = array_filter([
            $activity->club?->name ? 'Club: ' . $activity->club->name : null,
            $activity->description,
            $activity->purpose ? 'Purpose: ' . $activity->purpose : null,
        ]);

        return [
            'summary'     => $activity->title,
            'description' => implode("\n\n", $descriptionParts),
            'location'    => $activity->venue,
            'start'       => ['dateTime' => $start->format('Y-m-d\TH:i:s'), 'timeZone' => $tz],
            'end'         => ['dateTime' => $end->format('Y-m-d\TH:i:s'), 'timeZone' => $tz],
        ];
    }
}
