<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = [
        'club_id', 'title', 'description', 'date', 'time_start', 'time_end',
        'venue', 'purpose', 'expected_participants', 'status', 'event_type',
        'created_by', 'post_report_content', 'post_report_status',
    ];

    protected $casts = [
        'date'                  => 'date',
        'expected_participants' => 'integer',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
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
