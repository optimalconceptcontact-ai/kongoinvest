<?php

namespace App\Services\Matching;

use App\Data\MatchBreakdownItemData;
use App\Enums\MatchCriterion;
use App\Models\InvestorProfile;
use App\Models\Project;

class MatchScoringService
{
    public function __construct(
        private RuleMatchingService $rules,
        private AiSemanticAnalyzer $ai,
        private MatchingWeightsService $weights,
    ) {}

    /**
     * @return array{score: int, breakdown: array<int, array>}
     */
    public function score(Project $project, InvestorProfile $investor): array
    {
        // Step 1: normalize — eager-load what the rule scorers need so
        // they never trigger N+1s (spec §83).
        $project->loadMissing(['sectors', 'stages', 'latestFinancingRequest.components.instrument']);
        $investor->loadMissing(['sectors', 'stages', 'preferredInstruments']);

        // Step 2 + 3: rule matching + weighted score, for the 5
        // objective criteria.
        $items = [];
        foreach (MatchCriterion::ruleScored() as $criterion) {
            $score = match ($criterion) {
                MatchCriterion::Sector => $this->rules->scoreSector($project, $investor),
                MatchCriterion::Stage => $this->rules->scoreStage($project, $investor),
                MatchCriterion::Funding => $this->rules->scoreFunding($project, $investor),
                MatchCriterion::Geography => $this->rules->scoreGeography($project, $investor),
                MatchCriterion::Risk => $this->rules->scoreRisk($project, $investor),
                default => 0,
            };

            $items[] = MatchBreakdownItemData::make($criterion, $score, $this->weights->weightFor($criterion), 'rule');
        }

        // Step 4: AI semantic analysis, for the 3 narrative criteria.
        // Falls back to a neutral completeness heuristic — never zero,
        // never a hard failure — if AI is disabled or the call fails
        // (spec §113).
        $aiResult = $this->ai->analyze($project, $investor);

        foreach (MatchCriterion::aiScored() as $criterion) {
            [$score, $source] = $this->scoreAiCriterion($criterion, $project, $aiResult);
            $items[] = MatchBreakdownItemData::make($criterion, $score, $this->weights->weightFor($criterion), $source);
        }

        // Step 5: final score — sum of weighted contributions.
        $finalScore = (int) round(array_sum(array_map(fn (MatchBreakdownItemData $i) => $i->weightedContribution, $items)));

        return [
            'score' => max(0, min(100, $finalScore)),
            'breakdown' => array_map(fn (MatchBreakdownItemData $i) => $i->toArray(), $items),
        ];
    }

    /** @return array{0: int, 1: string} [score, source] */
    private function scoreAiCriterion(MatchCriterion $criterion, Project $project, ?MatchAiResult $ai): array
    {
        if ($ai !== null) {
            return [match ($criterion) {
                MatchCriterion::BusinessModel => $ai->businessModelScore,
                MatchCriterion::Traction => $ai->tractionScore,
                MatchCriterion::Impact => $ai->impactScore,
                default => 50,
            }, 'ai'];
        }

        // Fallback: reward having filled the field in at all. Crude, but
        // honest about being a placeholder — it never pretends to be a
        // semantic judgment it isn't.
        $field = match ($criterion) {
            MatchCriterion::BusinessModel => $project->business_model,
            MatchCriterion::Traction => $project->traction,
            MatchCriterion::Impact => $project->impact,
            default => null,
        };

        return [filled($field) ? 60 : 40, 'fallback'];
    }
}
