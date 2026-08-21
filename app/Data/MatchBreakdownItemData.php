<?php

namespace App\Data;

use App\Enums\MatchCriterion;
use Spatie\LaravelData\Data;

class MatchBreakdownItemData extends Data
{
    public function __construct(
        public string $key,
        public string $label,
        public int $score, // 0-100, this criterion alone
        public float $weight, // 0-1
        public float $weightedContribution, // score * weight, out of 100
        public string $source, // 'rule' | 'ai' | 'fallback'
    ) {}

    public static function make(MatchCriterion $criterion, int $score, float $weight, string $source): self
    {
        return new self(
            key: $criterion->value,
            label: $criterion->label(),
            score: $score,
            weight: $weight,
            weightedContribution: round($score * $weight, 1),
            source: $source,
        );
    }
}
