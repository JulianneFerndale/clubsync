<?php

namespace App\Services;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class SessionManager
{
    /**
     * Store Firebase auth data in the Laravel session.
     */
    public function store(string $idToken, string $uid, string $role, int $userId, bool $isAdmin = false): void
    {
        session([
            'firebase_id_token'  => $idToken,
            'firebase_uid'       => $uid,
            'firebase_user_role' => $role,
            'firebase_user_id'   => $userId,
            'firebase_is_admin'  => $isAdmin,
        ]);
    }

    /**
     * Refresh only the stored ID token (after a silent token refresh).
     */
    public function updateToken(string $idToken): void
    {
        session(['firebase_id_token' => $idToken]);
    }

    /**
     * Return the appropriate dashboard redirect based on the user's role.
     */
    public function redirectByRole(string $role): RedirectResponse
    {
        return match ($role) {
            'dsa'                          => Redirect::route('dsa.dashboard'),
            'adviser'                      => Redirect::route('adviser.dashboard'),
            'president', 'treasurer', 'mmo' => Redirect::route('officer.dashboard'),
            default                        => Redirect::route('member.dashboard'),
        };
    }
}
