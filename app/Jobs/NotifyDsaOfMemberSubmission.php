<?php

namespace App\Jobs;

use App\Models\Club;
use App\Models\ClubNotification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyDsaOfMemberSubmission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $clubId, private int $memberCount, private int $submittedBy) {}

    public function handle(): void
    {
        $club = Club::find($this->clubId);

        if (! $club) {
            return;
        }

        $submitter = User::find($this->submittedBy);

        foreach (User::where('role', 'dsa')->get() as $dsaUser) {
            ClubNotification::create([
                'recipient_id' => $dsaUser->id,
                'sender_type'  => 'system',
                'club_id'      => $club->id,
                'title'        => 'Member registration submitted',
                'body'         => ($submitter?->name ?? 'A club representative') . " submitted {$this->memberCount} member(s) from {$club->name} for review.",
                'action_url'   => route('dsa.clubs.members.index', $club),
            ]);
        }
    }
}
