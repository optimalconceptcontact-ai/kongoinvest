<?php

namespace App\Services\Matching;

/**
 * Plain value object, not a Spatie Data class — this never leaves the
 * matching service layer to become an HTTP response on its own (the
 * public-facing shape is MatchBreakdownItemData), so it doesn't need
 * Data's serialization machinery.
 */
final class MatchAiResult
{
    public function __construct(
        public readonly int $businessModelScore,
        public readonly int $tractionScore,
        public readonly int $impactScore,
        public readonly string $rationale,
    ) {}
}
