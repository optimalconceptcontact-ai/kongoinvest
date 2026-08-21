<?php

namespace App\Data;

use App\Models\Message;
use Spatie\LaravelData\Data;

class MessageData extends Data
{
    public function __construct(
        public int $id,
        public string $body,
        public int $senderId,
        public string $senderName,
        public string $createdAt,
    ) {}

    public static function fromModel(Message $message): self
    {
        return new self(
            id: $message->id,
            body: $message->body,
            senderId: $message->sender_id,
            senderName: $message->sender->name,
            createdAt: $message->created_at->toIso8601String(),
        );
    }
}
