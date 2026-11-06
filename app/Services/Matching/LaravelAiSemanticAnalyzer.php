<?php

namespace App\Services\Matching;

use App\Ai\Agents\MatchCompatibilityAgent;
use App\Models\InvestorProfile;
use App\Models\Project;
use App\Services\Ai\Concerns\InteractsWithAiProvider;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Responses\StructuredAgentResponse;

/**
 * The AI implementation of the matching semantic analyzer (spec §56).
 * Bound in place of NullSemanticAnalyzer only when
 * config('matching.ai_enabled') is on. Every path returns a validated
 * MatchAiResult or null — it never throws, so a slow or malformed
 * response degrades the match score to the completeness heuristic
 * instead of breaking the page (spec §113).
 */
class LaravelAiSemanticAnalyzer implements AiSemanticAnalyzer
{
    use InteractsWithAiProvider;

    public function analyze(Project $project, InvestorProfile $investor): ?MatchAiResult
    {
        if (! $this->aiAvailable()) {
            return null;
        }

        try {
            $agent = new MatchCompatibilityAgent($project, $investor);

            $response = $this->withTransientRetry(fn () => $agent->prompt(
                $agent->context(),
                provider: $this->aiProvider(),
                model: $this->aiModel(),
                timeout: $this->aiTimeout(),
            ));

            return $this->parse($response, $project, $investor);
        } catch (\Throwable $e) {
            Log::warning('AI matching analysis threw', [
                'message' => $e->getMessage(),
                'project_id' => $project->id,
                'investor_profile_id' => $investor->id,
            ]);

            return null;
        }
    }

    private function parse(mixed $response, Project $project, InvestorProfile $investor): ?MatchAiResult
    {
        $decoded = $response instanceof StructuredAgentResponse ? $response->toArray() : [];

        if (! isset($decoded['business_model_score'], $decoded['traction_score'], $decoded['impact_score'])) {
            Log::warning('AI matching analysis returned an unusable payload', [
                'project_id' => $project->id,
                'investor_profile_id' => $investor->id,
                'raw' => $response->text ?? null,
            ]);

            return null;
        }

        $clamp = fn ($v) => max(0, min(100, (int) $v));

        return new MatchAiResult(
            businessModelScore: $clamp($decoded['business_model_score']),
            tractionScore: $clamp($decoded['traction_score']),
            impactScore: $clamp($decoded['impact_score']),
            rationale: (string) ($decoded['rationale'] ?? ''),
        );
    }
}
