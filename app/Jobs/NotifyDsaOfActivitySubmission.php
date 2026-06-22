<?php

namespace App\Jobs;

use App\Models\Club;
use App\Models\ClubActivity;
use App\Models\ClubNotification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyDsaOfActivitySubmission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $activityId) {}

    public function handle(): void
    {
        $activity = ClubActivity::with('club')->find($this->activityId);

        if (! $activity || ! $activity->club) {
            return;
        }

        foreach (User::where('role', 'dsa')->get() as $dsaUser) {
            ClubNotification::create([
                'recipient_id' => $dsaUser->id,
                'sender_type'  => 'system',
                'club_id'      => $activity->club_id,
                'title'        => 'Activity awaiting approval',
                'body'         => "{$activity->club->name} submitted \"{$activity->title}\" for approval.",
                'action_url'   => route('dsa.activities.review'),
            ]);
        }
    }
}
