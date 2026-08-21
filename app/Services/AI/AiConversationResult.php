<?php

namespace App\Services\Ai;

final class AiConversationResult
{
    public function __construct(
        public readonly string $text,
        /** @var array<string, mixed>|null structured payload (e.g. AIImprovementData shape for the Coach) — null for a plain free-text answer */
        public readonly ?array $structured,
        public readonly int $tokensUsed,
    ) {}
}
