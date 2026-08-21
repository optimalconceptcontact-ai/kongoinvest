<?php

namespace App\Data;

use App\Models\AiMessage;
use Spatie\LaravelData\Data;

class AiMessageData extends Data
{
    public function __construct(
        public int $id,
        public string $role,
        public string $content,
        public string $createdAt,
    ) {}

    public static function fromModel(AiMessage $message): self
    {
        return new self($message->id, $message->role, $message->content, $message->created_at->toIso8601String());
    }
}
