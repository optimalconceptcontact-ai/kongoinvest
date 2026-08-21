<?php

namespace App\Data;

use App\Enums\MatchStatus;
use App\Models\MatchModel;
use Spatie\LaravelData\Data;

class MatchData extends Data
{
    public function __construct(
        public int $id,
        public int $score,
        public MatchStatus $status,
        public string $computedAt,
        /** @var MatchBreakdownItemData[] */
        public array $breakdown,
        public string $projectTitle,
        public string $projectSlug,
        public string $investorName,
    ) {}

    public static function fromModel(MatchModel $match): self
    {
        return new self(
            id: $match->id,
            score: $match->score,
            status: $match->status,
            computedAt: $match->computed_at->toIso8601String(),
            breakdown: array_map(fn ($row) => MatchBreakdownItemData::from($row), $match->breakdown),
            projectTitle: $match->project->title,
            projectSlug: $match->project->slug,
            investorName: $match->investorProfile->user->name,
        );
    }
}
