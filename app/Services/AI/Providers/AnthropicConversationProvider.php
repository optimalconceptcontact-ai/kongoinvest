<?php

namespace App\Services\Ai\Providers;

use App\Contracts\AiConversationProviderInterface;
use App\Services\Ai\AiConversationResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Shared by both the Entrepreneur AI Coach (spec §53) and the Investor
 * AI Advisor (spec §54) — same underlying model call, different system
 * prompts supplied by the caller (CoachService / InvestorAdvisorService).
 * Plain free-text responses (not the rigid JSON schema
 * AnthropicProjectAnalysisProvider requires) since these are
 * conversational Q&A, not a fixed-shape report.
 */
class AnthropicConversationProvider implements AiConversationProviderInterface
{
    public function respond(string $systemPrompt, array $history, string $userMessage): ?AiConversationResult
    {
        $apiKey = config('services.anthropic.api_key');

        if (blank($apiKey)) {
            return null;
        }

        $messages = array_merge($history, [['role' => 'user', 'content' => $userMessage]]);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
                ->timeout(30)
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => config('services.anthropic.model', 'claude-sonnet-4-6'),
                    'max_tokens' => 1200,
                    'system' => $systemPrompt,
                    'messages' => $messages,
                ]);

            if ($response->failed()) {
                Log::warning('AI conversation request failed', ['status' => $response->status()]);

                return null;
            }

            $json = $response->json();
            $text = collect($json['content'] ?? [])->firstWhere('type', 'text')['text'] ?? null;

            if (! $text) {
                return null;
            }

            return new AiConversationResult(
                text: $text,
                structured: null,
                tokensUsed: ($json['usage']['input_tokens'] ?? 0) + ($json['usage']['output_tokens'] ?? 0),
            );
        } catch (\Throwable $e) {
            Log::warning('AI conversation request threw', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
