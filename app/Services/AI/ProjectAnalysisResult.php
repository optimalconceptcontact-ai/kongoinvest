<?php

namespace App\Services\Ai;

/**
 * Plain internal value object — not Spatie Data — mirroring the pattern
 * MatchAiResult established in Phase 5. It never leaves this service
 * layer directly; ProjectAnalysisService maps it onto the AiAnalysis
 * model, which is what actually reaches Inertia/React.
 */
final class ProjectAnalysisResult
{
    public function __construct(
        public readonly int $investmentScore, // 0-100, spec §52
        /** @var array{market:int,team:int,traction:int,businessModel:int,scalability:int,risk:int,financialPotential:int} */
        public readonly array $subScores,
        public readonly string $executiveSummary,
        public readonly string $businessModelAssessment,
        public readonly string $marketPotential,
        public readonly string $problemSolutionFit,
        public readonly string $competitiveAdvantage,
        public readonly string $teamAssessment,
        public readonly string $tractionAssessment,
        public readonly string $financialPotential,
        public readonly string $scalability,
        /** @var string[] */
        public readonly array $strengths,
        /** @var string[] */
        public readonly array $weaknesses,
        /** @var string[] */
        public readonly array $risks,
        /** @var string[] */
        public readonly array $redFlags,
        /** @var string[] */
        public readonly array $dueDiligenceQuestions,
        public readonly string $fundingAssessment,
        public readonly string $recommendation,
        public readonly int $tokensUsed,
    ) {}
}
