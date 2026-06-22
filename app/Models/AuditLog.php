<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Append-only, tamper-evident audit log (POLICY.md §Data Privacy).
 *
 * Records every data modification, approval action, and AI content generation
 * event with a timestamp, the actor's UID, and the affected resource. Each entry
 * is chained to the previous one via a SHA-256 hash, so tampering with any past
 * row invalidates every hash after it.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null; // append-only — no updates

    protected $fillable = [
        'actor_id', 'action', 'resource_type', 'resource_id',
        'metadata', 'previous_hash', 'hash',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Write an audit entry. Safe to call from controllers (auth context) or queued
     * jobs (pass $actorId, or leave null for system-generated events).
     *
     * @param  string       $action     Dotted event name, e.g. 'ai.narrative.generated'
     * @param  Model|null   $resource   The affected model (its type + id are recorded)
     * @param  array        $metadata   Extra context to persist
     * @param  int|null     $actorId    Acting user id; defaults to the session user when available
     */
    public static function record(string $action, ?Model $resource = null, array $metadata = [], ?int $actorId = null): self
    {
        // Resolve the acting user, treating "no session" (0/null) as a system event.
        $actorId = $actorId ?: (function_exists('auth_user_id') ? auth_user_id() : null);
        $actorId = $actorId ?: null;

        return DB::transaction(function () use ($action, $resource, $metadata, $actorId) {
            $previous = static::query()->orderByDesc('id')->lockForUpdate()->first();
            $previousHash = $previous?->hash;

            $createdAt = now();

            $payload = json_encode([
                'actor_id'      => $actorId,
                'action'        => $action,
                'resource_type' => $resource ? $resource::class : null,
                'resource_id'   => $resource?->getKey(),
                'metadata'      => $metadata,
                'created_at'    => $createdAt->toIso8601String(),
            ]);

            $hash = hash('sha256', ($previousHash ?? '') . $payload);

            return static::create([
                'actor_id'      => $actorId,
                'action'        => $action,
                'resource_type' => $resource ? $resource::class : null,
                'resource_id'   => $resource?->getKey(),
                'metadata'      => $metadata,
                'previous_hash' => $previousHash,
                'hash'          => $hash,
                'created_at'    => $createdAt,
            ]);
        });
    }
}
