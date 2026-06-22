<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Club extends Model
{
    protected $fillable = [
        'slug', 'acronym', 'adviser', 'club_type',
        'course_slug', 'department_slug', 'description',
        'is_active', 'name', 'profile_photo_url',
        'type', 'college_id', 'adviser_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(ClubMember::class);
    }

    public function officers(): HasMany
    {
        return $this->hasMany(ClubOfficer::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ClubActivity::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function adviserUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_slug', 'slug');
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'club_course');
    }

    public function scopeAcademic($query)
    {
        return $query->where(fn ($q) => $q->where('type', 'academic')->orWhere('club_type', 'Academic'));
    }

    public function scopeNonAcademic($query)
    {
        return $query->where(fn ($q) => $q->where('type', 'non_academic')->orWhere('club_type', 'Non-Academic'));
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getActiveMemberCountAttribute(): int
    {
        return $this->members()->where('status', 'active')->count();
    }
}
