<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiNotificationQueue extends Model
{
    protected $table = 'ai_notification_queue';

    protected $fillable = [
        'club_id', 'gap_type', 'description', 'draft_content', 'dsa_edited_content',
        'status', 'reviewed_by', 'reviewed_at', 'sent_at', 'ai_available',
        'is_resolved', 'resolution_note', 'resolved_at', 'resolved_by',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'sent_at'     => 'datetime',
        'resolved_at' => 'datetime',
        'ai_available' => 'boolean',
        'is_resolved'  => 'boolean',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function finalContent(): ?string
    {
        return $this->dsa_edited_content ?: $this->draft_content;
    }
}
