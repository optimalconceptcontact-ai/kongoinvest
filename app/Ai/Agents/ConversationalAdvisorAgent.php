<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\MessageRole;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * The single conversational agent behind both the Entrepreneur AI Coach
 * (spec §53) and the Investor AI Advisor (spec §54). The two features
 * are the same model call with a different system prompt and a different
 * conversation — CoachService / InvestorAdvisorService own the prompt
 * text and the AiConversation persistence, this class just carries them
 * to the model.
 *
 * Instructions and prior turns are injected per request rather than
 * hard-coded so one agent can serve every conversation.
 */
class ConversationalAdvisorAgent implements Agent, Conversational
{
    use Promptable;

    /**
     * @param  array<int, array{role?: ?string, content?: ?string}>  $history  Prior turns, oldest first, excluding the message being answered now.
     */
    public function __construct(
        private string $instructions,
        private array $history = [],
    ) {}

    public function instructions(): Stringable|string
    {
        return $this->instructions;
    }

    /**
     * Only user/assistant turns with real content survive — system rows
     * (the calling service filters them out already) and blanks are
     * dropped rather than coerced into a role the model would misread.
     *
     * @return iterable<int, Message>
     */
    public function messages(): iterable
    {
        return collect($this->history)
            ->map(fn (array $m) => [
                'role' => MessageRole::tryFrom((string) ($m['role'] ?? '')),
                'content' => trim((string) ($m['content'] ?? '')),
            ])
            ->filter(fn (array $m) => $m['role'] !== null && $m['content'] !== '')
            ->map(fn (array $m) => new Message($m['role'], $m['content']))
            ->values()
            ->all();
    }
}
