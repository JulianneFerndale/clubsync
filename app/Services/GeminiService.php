<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey  = config('gemini.api_key', '');
        $this->model   = config('gemini.model', 'gemini-1.5-flash');
        $this->baseUrl = config('gemini.base_url');
    }

    public function isAvailable(): bool
    {
        return ! empty($this->apiKey) && $this->apiKey !== 'your_gemini_api_key';
    }

    /**
     * Generate an announcement draft for a student club.
     *
     * @param  string  $title    The announcement title / topic
     * @param  string  $type     'announcement' or 'letter'
     * @param  string  $clubName The club's name
     * @param  string  $context  Optional extra context from the officer
     * @return string|null       Generated content, or null on failure
     */
    public function draftAnnouncement(string $title, string $type, string $clubName, string $context = ''): ?string
    {
        if (! $this->isAvailable()) {
            return null;
        }

        $typeLabel = $type === 'letter' ? 'formal letter' : 'announcement';

        $prompt = <<<PROMPT
You are a student organization communications assistant for {$clubName}, a college club in the Philippines.

Write a professional {$typeLabel} for the following topic:
Title: {$title}
{$context}

Requirements:
- Tone: professional but friendly, appropriate for a college club
- Length: 2–4 short paragraphs
- Language: English
- Do NOT include greeting headers, sign-offs, or "Dear..." lines — only the body content
- Output plain text only, no markdown formatting
PROMPT;

        try {
            $url = "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::timeout(15)->post($url, [
                'contents' => [
                    [
                        'parts' => [['text' => $prompt]],
                    ],
                ],
                'generationConfig' => [
                    'temperature'     => 0.7,
                    'maxOutputTokens' => 512,
                ],
            ]);

            if (! $response->successful()) {
                Log::warning('Gemini API error: ' . $response->body());
                return null;
            }

            return $response->json('candidates.0.content.parts.0.text');
        } catch (\Throwable $e) {
            Log::warning('GeminiService::draftAnnouncement failed: ' . $e->getMessage());
            return null;
        }
    }
}
