<?php

namespace App\Models;

use App\Events\NotificationCreated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class ClubNotification extends Model
{
    protected $table = 'notifications';

    /**
     * Push every newly-created notification to its recipient in real time.
     * Broadcasting is best-effort: a delivery/connection failure (e.g. the Reverb
     * server is down) is logged but never blocks persisting the notification.
     */
    protected static function booted(): void
    {
        static::created(function (ClubNotification $notification) {
            if (! $notification->recipient_id) {
                return;
            }

            try {
                broadcast(new NotificationCreated($notification));
            } catch (\Throwable $e) {
                Log::warning('Notification broadcast failed: ' . $e->getMessage());
            }
        });
    }

    protected $fillable = [
        'recipient_id', 'sender_type', 'club_id',
        'title', 'body', 'is_read', 'action_url',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public static function unreadCountFor(int $userId): int
    {
        return static::where('recipient_id', $userId)->where('is_read', false)->count();
    }
}
