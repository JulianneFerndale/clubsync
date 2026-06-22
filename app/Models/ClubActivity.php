<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ClubActivity extends Model
{
    protected $fillable = [
        'club_id', 'title', 'description', 'date', 'time_start', 'time_end',
        'venue', 'purpose', 'expected_participants', 'status', 'completed_at', 'event_type',
        'created_by', 'post_report_content', 'post_report_status',
        'activity_type', 'approval_status', 'dsa_remarks', 'approval_letter_path',
        'approved_at', 'approved_by',
    ];

    protected $casts = [
        'date'                  => 'date',
        'expected_participants' => 'integer',
        'approved_at'           => 'datetime',
        'completed_at'          => 'datetime',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeNeedsApproval($query)
    {
        return $query->where('activity_type', '!=', 'internal_meeting');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function changeLogs(): HasMany
    {
        return $this->hasMany(ActivityChangeLog::class)->orderByDesc('created_at');
    }

    public function chedReport(): HasOne
    {
        return $this->hasOne(ChedReport::class, 'club_activity_id');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', today())->where('status', 'scheduled')->orderBy('date');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
