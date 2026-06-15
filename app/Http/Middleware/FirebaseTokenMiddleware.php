<?php

namespace App\Http\Middleware;

use App\Services\AuthManager;
use App\Services\SessionManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class FirebaseTokenMiddleware
{
    public function __construct(
        private AuthManager $auth,
        private SessionManager $session,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $idToken = session('firebase_id_token');

        if (! $idToken) {
            return $this->unauthenticated($request);
        }

        if ($this->auth->isTokenExpired($idToken)) {
            $refreshToken = $request->cookie('clubsync_refresh') ?? session('firebase_refresh_token');

            if (! $refreshToken) {
                return $this->sessionExpired($request);
            }

            try {
                $newToken = $this->auth->refreshIdToken($refreshToken);
                $this->session->updateToken($newToken);
            } catch (\Throwable $e) {
                Log::warning('Firebase token refresh failed: ' . $e->getMessage());
                return $this->sessionExpired($request);
            }
        }

        $response = $next($request);

        // Prevent the browser from caching authenticated pages so that
        // the back button after logout never re-shows the dashboard.
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    private function unauthenticated(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return redirect()->route('login');
    }

    private function sessionExpired(Request $request): Response
    {
        $this->auth->revokeSession();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Your session has expired. Please log in again.'], 401);
        }

        return redirect()->route('login')
            ->with('error', 'Your session has expired. Please log in again.');
    }
}
