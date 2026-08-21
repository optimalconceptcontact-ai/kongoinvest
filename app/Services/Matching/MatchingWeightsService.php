<?php

namespace App\Services\Matching;

use App\Enums\MatchCriterion;
use App\Models\MatchingCriteriaWeight;
use Illuminate\Support\Facades\Cache;

class MatchingWeightsService
{
    /**
     * The defaults from spec §23, used only if the
     * matching_criteria_weights table is empty (shouldn't happen once
     * MatchingWeightsSeeder has run, but a scoring service should never
     * hard-fail because an admin table is empty).
     */
    private const DEFAULTS = [
        'sector' => 0.20,
        'funding' => 0.20,
        'stage' => 0.15,
        'business' => 0.15,
        'geography' => 0.10,
        'traction' => 0.10,
        'impact' => 0.05,
        'risk' => 0.05,
    ];

    /** @return array<string, float> criterion key => weight (0-1) */
    public function current(): array
    {
        return Cache::remember('matching.weights', now()->addMinutes(10), function () {
            $rows = MatchingCriteriaWeight::pluck('weight', 'criterion')->map(fn ($w) => (float) $w)->toArray();

            return count($rows) === count(MatchCriterion::cases()) ? $rows : self::DEFAULTS;
        });
    }

    public function weightFor(MatchCriterion $criterion): float
    {
        return $this->current()[$criterion->value] ?? (self::DEFAULTS[$criterion->value] ?? 0.0);
    }

    public static function defaults(): array
    {
        return self::DEFAULTS;
    }
}
