<?php

namespace App\Services\Ai;

use App\Contracts\ProjectAnalysisProviderInterface;
use App\Enums\AiInteractionType;
use App\Enums\AiRequestStatus;
use App\Models\AiAnalysis;
use App\Models\Project;
use App\Models\User;
use App\Services\AuditLogger;

class ProjectAnalysisService
{
    /** Spec §55 — stored verbatim on every analysis row, not just rendered from a constant in React. */
    public const DISCLAIMER = "Analyse générée par IA. Ces informations sont fournies à titre informatif uniquement et ne constituent pas un conseil financier, juridique ou d'investissement.";

    public function __construct(
        private ProjectAnalysisProviderInterface $provider,
        private AiUsageService $usage,
        private AuditLogger $audit,
    ) {}

    public function analyze(User $requester, Project $project): AiAnalysis
    {
        $this->usage->assertWithinQuota($requester);

        $result = $this->provider->analyze($project);

        if ($result === null) {
            $this->usage->record($requester, AiInteractionType::ProjectAnalysis, AiRequestStatus::Failed, $project);

            // A row is still persisted in the Failed state — spec §113:
            // "permettre retry" needs something in the UI to retry
            // *from*, and §49's audit trail should show the attempt
            // happened even though it produced nothing usable.
            return AiAnalysis::create([
                'project_id' => $project->id,
                'requested_by_user_id' => $requester->id,
                'status' => AiRequestStatus::Failed->value,
                'failure_reason' => "L'analyse IA n'a pas pu être générée. Réessayez dans quelques instants.",
            ]);
        }

        $this->usage->record($requester, AiInteractionType::ProjectAnalysis, AiRequestStatus::Completed, $project, config('ai.features.provider'), $result->tokensUsed);

        $analysis = AiAnalysis::create([
            'project_id' => $project->id,
            'requested_by_user_id' => $requester->id,
            'status' => AiRequestStatus::Completed->value,
            'provider' => config('ai.features.provider'),
            'investment_score' => $result->investmentScore,
            'tokens_used' => $result->tokensUsed,
            'content' => [
                'subScores' => $result->subScores,
                'executiveSummary' => $result->executiveSummary,
                'businessModelAssessment' => $result->businessModelAssessment,
                'marketPotential' => $result->marketPotential,
                'problemSolutionFit' => $result->problemSolutionFit,
                'competitiveAdvantage' => $result->competitiveAdvantage,
                'teamAssessment' => $result->teamAssessment,
                'tractionAssessment' => $result->tractionAssessment,
                'financialPotential' => $result->financialPotential,
                'scalability' => $result->scalability,
                'strengths' => $result->strengths,
                'weaknesses' => $result->weaknesses,
                'risks' => $result->risks,
                'redFlags' => $result->redFlags,
                'dueDiligenceQuestions' => $result->dueDiligenceQuestions,
                'fundingAssessment' => $result->fundingAssessment,
                'recommendation' => $result->recommendation,
                'disclaimer' => self::DISCLAIMER,
            ],
        ]);

        $this->audit->log($requester, 'ai.project_analysis_generated', $analysis, ['project_id' => $project->id]);

        return $analysis;
    }
}
