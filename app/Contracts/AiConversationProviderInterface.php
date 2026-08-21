<?php

namespace App\Contracts;

use App\Services\Ai\AiConversationResult;

interface AiConversationProviderInterface
{
    /**
     * @param  array<int, array{role: string, content: string}>  $history
     */
    public function respond(string $systemPrompt, array $history, string $userMessage): ?AiConversationResult;
}
