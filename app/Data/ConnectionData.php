<?php

namespace App\Data;

use App\Enums\ConnectionStatus;
use App\Models\Connection;
use Spatie\LaravelData\Data;

class ConnectionData extends Data
{
    public function __construct(
        public int $id,
        public ConnectionStatus $status,
        public ?string $message,
        public string $projectTitle,
        public string $projectSlug,
        public string $investorName,
        public string $createdAt,
        public ?int $dealRoomId,
    ) {}

    public static function fromModel(Connection $connection): self
    {
        return new self(
            id: $connection->id,
            status: $connection->status,
            message: $connection->message,
            projectTitle: $connection->project->title,
            projectSlug: $connection->project->slug,
            investorName: $connection->investorProfile->user->name,
            createdAt: $connection->created_at->toIso8601String(),
            dealRoomId: $connection->dealRoom?->id,
        );
    }
}
