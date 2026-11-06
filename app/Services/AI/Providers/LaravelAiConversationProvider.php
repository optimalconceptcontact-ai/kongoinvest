<?php

namespace App\Services\Ai\Providers;

use App\Ai\Agents\ConversationalAdvisorAgent;
use App\Contracts\AiConversationProviderInterface;
use App\Services\Ai\AiConversationResult;
use App\Services\Ai\Concerns\InteractsWithAiProvider;
use Illuminate\Support\Facades\Log;

/**
 * Backs both the Entrepreneur AI Coach (spec §53) and the Investor AI
 * Advisor (spec §54) — same model call, different system prompt supplied
 * by the calling service. Free-text answers, not a fixed schema, since
 * these are conversational Q&A.
 *
 * Returns null on any failure so CoachService / InvestorAdvisorService
 * can write the "retry later" assistant message instead of 500-ing.
 */
class LaravelAiConversationProvider implements AiConversationProviderInterface
{
    use InteractsWithAiProvider;

    public function respond(string $systemPrompt, array $history, string $userMessage): ?AiConversationResult
    {
        if (! $this->aiAvailable()) {
            return null;
        }

        try {
            $response = $this->withTransientRetry(fn () => (new ConversationalAdvisorAgent($systemPrompt, $history))->prompt(
                $userMessage,
                provider: $this->aiProvider(),
                model: $this->aiModel(),
                timeout: $this->aiTimeout(),
            ));

            $text = trim($response->text);

            if ($text === '') {
                return null;
            }

            return new AiConversationResult(
                text: $text,
                structured: null,
                tokensUsed: $this->tokensUsed($response->usage),
            );
        } catch (\Throwable $e) {
            Log::warning('AI conversation request threw', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
