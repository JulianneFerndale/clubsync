<?php

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('chains audit log hashes tamper-evidently', function () {
    $first  = AuditLog::record('test.one', null, ['a' => 1]);
    $second = AuditLog::record('test.two', null, ['b' => 2]);

    // First entry starts the chain.
    expect($first->previous_hash)->toBeNull();
    expect($first->hash)->not->toBeNull();

    // Second entry links to the first.
    expect($second->previous_hash)->toBe($first->hash);

    // Hash actually covers the payload (recomputing matches what was stored).
    $recomputed = hash('sha256', $first->hash . json_encode([
        'actor_id'      => null,
        'action'        => 'test.two',
        'resource_type' => null,
        'resource_id'   => null,
        'metadata'      => ['b' => 2],
        'created_at'    => $second->created_at->toIso8601String(),
    ]));
    expect($second->hash)->toBe($recomputed);
});
