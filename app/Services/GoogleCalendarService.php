<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Minimal, dependency-free Google Calendar client.
 *
 * Authenticates as a Google Workspace service account using domain-wide
 * delegation (a signed JWT exchanged for an OAuth access token) and talks to
 * the Calendar v3 REST API directly — no google/apiclient package required.
 *
 * All methods are safe to call unconditionally: when sync is disabled or the
 * key file is missing, enabled() returns false and callers should skip.
 */
class GoogleCalendarService
{
    private const TOKEN_CACHE_KEY = 'google_calendar_access_token';
    private const SCOPE = 'https://www.googleapis.com/auth/calendar';
    private const API_BASE = 'https://www.googleapis.com/calendar/v3';

    private array $config;

    public function __construct()
    {
        $this->config = config('google.calendar');
    }

    public function enabled(): bool
    {
        return ($this->config['enabled'] ?? false)
            && ! empty($this->config['credentials'])
            && is_file($this->config['credentials']);
    }

    /** Create an event and return its Google event id, or null on failure. */
    public function createEvent(array $event): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        $response = Http::withToken($this->accessToken())
            ->post(self::API_BASE . '/calendars/' . $this->calendarId() . '/events', $event);

        if ($response->successful()) {
            return $response->json('id');
        }

        Log::warning('Google Calendar createEvent failed: ' . $response->body());

        return null;
    }

    /** Update an existing event; returns true on success. Recreates via caller if it 404s. */
    public function updateEvent(string $eventId, array $event): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        $response = Http::withToken($this->accessToken())
            ->put(self::API_BASE . '/calendars/' . $this->calendarId() . '/events/' . rawurlencode($eventId), $event);

        if ($response->successful()) {
            return true;
        }

        Log::warning('Google Calendar updateEvent failed (' . $response->status() . '): ' . $response->body());

        return false;
    }

    public function deleteEvent(string $eventId): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        $response = Http::withToken($this->accessToken())
            ->delete(self::API_BASE . '/calendars/' . $this->calendarId() . '/events/' . rawurlencode($eventId));

        // 410/404 mean it's already gone — treat as success (idempotent).
        if ($response->successful() || in_array($response->status(), [404, 410], true)) {
            return true;
        }

        Log::warning('Google Calendar deleteEvent failed (' . $response->status() . '): ' . $response->body());

        return false;
    }

    private function calendarId(): string
    {
        return rawurlencode($this->config['calendar_id'] ?? 'primary');
    }

    /** Fetch (and cache) an OAuth access token via the service-account JWT grant. */
    private function accessToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, now()->addMinutes(50), function () {
            $key = json_decode((string) file_get_contents($this->config['credentials']), true);

            if (! is_array($key) || empty($key['client_email']) || empty($key['private_key'])) {
                throw new RuntimeException('Invalid Google service-account key file.');
            }

            $tokenUri = $key['token_uri'] ?? 'https://oauth2.googleapis.com/token';
            $assertion = $this->buildSignedJwt($key, $tokenUri);

            $response = Http::asForm()->post($tokenUri, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $assertion,
            ]);

            if (! $response->successful() || ! $response->json('access_token')) {
                throw new RuntimeException('Google token exchange failed: ' . $response->body());
            }

            return $response->json('access_token');
        });
    }

    private function buildSignedJwt(array $key, string $tokenUri): string
    {
        $now = time();

        $claims = [
            'iss'   => $key['client_email'],
            'scope' => self::SCOPE,
            'aud'   => $tokenUri,
            'iat'   => $now,
            'exp'   => $now + 3600,
        ];

        // Domain-wide delegation: impersonate a real Workspace user.
        if (! empty($this->config['impersonate'])) {
            $claims['sub'] = $this->config['impersonate'];
        }

        $segments = [
            $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])),
            $this->base64UrlEncode(json_encode($claims)),
        ];

        $signingInput = implode('.', $segments);

        $signature = '';
        if (! openssl_sign($signingInput, $signature, $key['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Failed to sign Google JWT with the service-account private key.');
        }

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
