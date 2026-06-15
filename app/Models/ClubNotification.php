<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubNotification extends Model
{
    protected $table = 'notifications';

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
