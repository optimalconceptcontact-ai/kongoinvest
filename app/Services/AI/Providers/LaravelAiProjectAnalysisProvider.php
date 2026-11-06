<?php

namespace App\Services\Ai\Providers;

use App\Ai\Agents\ProjectAnalysisAgent;
use App\Contracts\ProjectAnalysisProviderInterface;
use App\Models\Project;
use App\Services\Ai\Concerns\InteractsWithAiProvider;
use App\Services\Ai\ProjectAnalysisResult;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Responses\StructuredAgentResponse;

/**
 * The only class that talks to the model for project analysis (spec §56).
 * ProjectAnalysisService depends on the interface, never on this class,
 * so the provider can be swapped or disabled without touching the
 * persistence / quota / audit logic.
 *
 * Every path returns either a validated ProjectAnalysisResult or null —
 * a slow, failed, or malformed response must never throw (spec §113).
 */
class LaravelAiProjectAnalysisProvider implements ProjectAnalysisProviderInterface
{
    use InteractsWithAiProvider;

    public function analyze(Project $project): ?ProjectAnalysisResult
    {
        if (! $this->aiAvailable()) {
            return null;
        }

        try {
            $agent = new ProjectAnalysisAgent($project);

            $response = $this->withTransientRetry(fn () => $agent->prompt(
                $agent->context(),
                provider: $this->aiProvider(),
                model: $this->aiModel(),
                timeout: $this->aiTimeout(),
            ));

            return $this->parse($response, $project);
        } catch (\Throwable $e) {
            Log::warning('AI project analysis threw', ['message' => $e->getMessage(), 'project_id' => $project->id]);

            return null;
        }
    }

    private function parse(mixed $response, Project $project): ?ProjectAnalysisResult
    {
        $decoded = $response instanceof StructuredAgentResponse ? $response->toArray() : [];

        if (! isset($decoded['investment_score'], $decoded['sub_scores']) || ! is_array($decoded['sub_scores'])) {
            Log::warning('AI project analysis returned an unusable payload', [
                'project_id' => $project->id,
                'raw' => $response->text ?? null,
            ]);

            return null;
        }

        $clamp = fn ($v) => max(0, min(100, (int) $v));
        $strings = fn ($v) => is_array($v) ? array_values(array_map('strval', $v)) : [];
        $sub = $decoded['sub_scores'];

        return new ProjectAnalysisResult(
            investmentScore: $clamp($decoded['investment_score']),
            subScores: [
                'market' => $clamp($sub['market'] ?? 50),
                'team' => $clamp($sub['team'] ?? 50),
                'traction' => $clamp($sub['traction'] ?? 50),
                'businessModel' => $clamp($sub['business_model'] ?? 50),
                'scalability' => $clamp($sub['scalability'] ?? 50),
                'risk' => $clamp($sub['risk'] ?? 50),
                'financialPotential' => $clamp($sub['financial_potential'] ?? 50),
            ],
            executiveSummary: (string) ($decoded['executive_summary'] ?? ''),
            businessModelAssessment: (string) ($decoded['business_model_assessment'] ?? ''),
            marketPotential: (string) ($decoded['market_potential'] ?? ''),
            problemSolutionFit: (string) ($decoded['problem_solution_fit'] ?? ''),
            competitiveAdvantage: (string) ($decoded['competitive_advantage'] ?? ''),
            teamAssessment: (string) ($decoded['team_assessment'] ?? ''),
            tractionAssessment: (string) ($decoded['traction_assessment'] ?? ''),
            financialPotential: (string) ($decoded['financial_potential'] ?? ''),
            scalability: (string) ($decoded['scalability'] ?? ''),
            strengths: $strings($decoded['strengths'] ?? []),
            weaknesses: $strings($decoded['weaknesses'] ?? []),
            risks: $strings($decoded['risks'] ?? []),
            redFlags: $strings($decoded['red_flags'] ?? []),
            dueDiligenceQuestions: $strings($decoded['due_diligence_questions'] ?? []),
            fundingAssessment: (string) ($decoded['funding_assessment'] ?? ''),
            recommendation: (string) ($decoded['recommendation'] ?? ''),
            tokensUsed: $this->tokensUsed($response->usage),
        );
    }
}
