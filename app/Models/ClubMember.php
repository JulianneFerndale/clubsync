<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubMember extends Model
{
    protected $fillable = ['club_id', 'user_id', 'role', 'date_joined', 'joined_at', 'status'];

    protected $casts = [
        'date_joined' => 'datetime',
        'joined_at'   => 'datetime',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
