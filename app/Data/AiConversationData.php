<?php

namespace App\Data;

use App\Models\AiConversation;
use Spatie\LaravelData\Data;

class AiConversationData extends Data
{
    public function __construct(
        public int $id,
        /** @var AiMessageData[] */
        public array $messages,
    ) {}

    public static function fromModel(AiConversation $conversation): self
    {
        return new self(
            id: $conversation->id,
            messages: $conversation->messages->map(fn ($m) => AiMessageData::fromModel($m))->all(),
        );
    }
}
