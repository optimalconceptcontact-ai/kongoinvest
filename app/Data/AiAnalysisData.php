<?php

namespace App\Data;

use App\Models\AiAnalysis;
use Spatie\LaravelData\Data;

/**
 * Spec §57's ProjectAnalysisData, named to match this app's existing
 * Ai* naming convention. Shapes exactly what the AIAnalysisSheet
 * component renders — every §51 section, the score + sub-scores from
 * §52, and the §55 disclaimer, never a raw content blob the frontend
 * has to interpret itself.
 */
class AiAnalysisData extends Data
{
    public function __construct(
        public int $id,
        public string $status,
        public ?int $investmentScore,
        public ?array $subScores,
        public ?string $executiveSummary,
        public ?string $businessModelAssessment,
        public ?string $marketPotential,
        public ?string $problemSolutionFit,
        public ?string $competitiveAdvantage,
        public ?string $teamAssessment,
        public ?string $tractionAssessment,
        public ?string $financialPotential,
        public ?string $scalability,
        /** @var string[] */
        public array $strengths,
        /** @var string[] */
        public array $weaknesses,
        /** @var string[] */
        public array $risks,
        /** @var string[] */
        public array $redFlags,
        /** @var string[] */
        public array $dueDiligenceQuestions,
        public ?string $fundingAssessment,
        public ?string $recommendation,
        public ?string $disclaimer,
        public ?string $failureReason,
        public string $createdAt,
    ) {}

    public static function fromModel(AiAnalysis $analysis): self
    {
        $c = $analysis->content ?? [];

        return new self(
            id: $analysis->id,
            status: $analysis->status->value,
            investmentScore: $analysis->investment_score,
            subScores: $c['subScores'] ?? null,
            executiveSummary: $c['executiveSummary'] ?? null,
            businessModelAssessment: $c['businessModelAssessment'] ?? null,
            marketPotential: $c['marketPotential'] ?? null,
            problemSolutionFit: $c['problemSolutionFit'] ?? null,
            competitiveAdvantage: $c['competitiveAdvantage'] ?? null,
            teamAssessment: $c['teamAssessment'] ?? null,
            tractionAssessment: $c['tractionAssessment'] ?? null,
            financialPotential: $c['financialPotential'] ?? null,
            scalability: $c['scalability'] ?? null,
            strengths: $c['strengths'] ?? [],
            weaknesses: $c['weaknesses'] ?? [],
            risks: $c['risks'] ?? [],
            redFlags: $c['redFlags'] ?? [],
            dueDiligenceQuestions: $c['dueDiligenceQuestions'] ?? [],
            fundingAssessment: $c['fundingAssessment'] ?? null,
            recommendation: $c['recommendation'] ?? null,
            disclaimer: $c['disclaimer'] ?? ($analysis->status->value === 'completed' ? \App\Services\Ai\ProjectAnalysisService::DISCLAIMER : null),
            failureReason: $analysis->failure_reason,
            createdAt: $analysis->created_at->toIso8601String(),
        );
    }
}
