<?php

namespace App\Services\Ai\Providers;

use App\Contracts\AiConversationProviderInterface;
use App\Services\Ai\AiConversationResult;

class NullAiConversationProvider implements AiConversationProviderInterface
{
    public function respond(string $systemPrompt, array $history, string $userMessage): ?AiConversationResult
    {
        return null;
    }
}
