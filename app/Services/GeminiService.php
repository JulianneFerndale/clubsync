<?php

namespace App\Services;

use App\Models\AiNotificationQueue;
use App\Models\Club;
use App\Models\ClubActivity;
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
                    // gemini-2.5-flash is a thinking model; disable thinking so the
                    // token budget is spent on the answer, not internal reasoning.
                    'thinkingConfig'  => ['thinkingBudget' => 0],
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

    /**
     * Draft a post-event narrative for a club, describing what happened at a completed activity.
     * The output is a DRAFT only — it must pass through adviser review before members see it.
     *
     * @return string|null Generated narrative, or null on failure / when AI is unavailable
     */
    public function draftClubNarrative(ClubActivity $activity, Club $club): ?string
    {
        if (! $this->isAvailable()) {
            return null;
        }

        $date    = optional($activity->date)->format('F j, Y') ?? 'a recent date';
        $venue   = $activity->venue ?: 'the club venue';
        $purpose = $activity->purpose ?: $activity->description ?: 'a club activity';
        $report  = $activity->post_report_content
            ? "Officer's post-event report:\n{$activity->post_report_content}"
            : '';

        $prompt = <<<PROMPT
You are a student organization communications assistant for {$club->name}, a college club at Saint Columban College of Pagadian City, Philippines.

Write a short post-event narrative describing what happened at this completed activity, suitable for the club bulletin so members and the community can see what the club has been up to.

Activity: {$activity->title}
Date: {$date}
Venue: {$venue}
Purpose / objectives: {$purpose}
{$report}

Requirements:
- Tone: warm, engaging, and factual — like a recap a club member would enjoy reading
- Length: 2-3 short paragraphs
- Language: English
- Base the narrative ONLY on the details provided above. Do NOT invent specific names, numbers, quotes, or outcomes that are not given.
- Do NOT include headers, titles, or sign-offs — only the narrative body
- Output plain text only, no markdown formatting
PROMPT;

        try {
            $url = "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::timeout(15)->post($url, [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'temperature'     => 0.7,
                    'maxOutputTokens' => 512,
                    // gemini-2.5-flash is a thinking model; disable thinking so the
                    // token budget is spent on the answer, not internal reasoning.
                    'thinkingConfig'  => ['thinkingBudget' => 0],
                ],
            ]);

            if (! $response->successful()) {
                Log::warning('Gemini API error: ' . $response->body());
                return null;
            }

            return $response->json('candidates.0.content.parts.0.text');
        } catch (\Throwable $e) {
            Log::warning('GeminiService::draftClubNarrative failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Draft a compliance/violation reminder statement for a club, on behalf of the DSA.
     */
    public function generateViolationStatement(AiNotificationQueue $violation, Club $club): ?string
    {
        if (! $this->isAvailable()) {
            return null;
        }

        $prompt = <<<PROMPT
You are drafting an official compliance reminder from the Dean of Student Affairs of Saint Columban College of Pagadian City to the officers and adviser of the {$club->name} club.

The violation is: {$violation->gap_type}.
Details: {$violation->description}.

Write a professional, firm, but respectful reminder statement in 2-3 short paragraphs. Include what action is required and a general deadline of 5 working days. Do not include a signature block — it will be added separately. Output plain text only, no markdown formatting.
PROMPT;

        try {
            $url = "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::timeout(15)->post($url, [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'temperature'     => 0.6,
                    'maxOutputTokens' => 512,
                    // Disable thinking (see draftAnnouncement) so the answer isn't starved of tokens.
                    'thinkingConfig'  => ['thinkingBudget' => 0],
                ],
            ]);

            if (! $response->successful()) {
                Log::warning('Gemini API error: ' . $response->body());
                return null;
            }

            return $response->json('candidates.0.content.parts.0.text');
        } catch (\Throwable $e) {
            Log::warning('GeminiService::generateViolationStatement failed: ' . $e->getMessage());
            return null;
        }
    }
}
