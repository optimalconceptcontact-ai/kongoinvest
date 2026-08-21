<?php

namespace App\Services\Matching;

use App\Models\InvestorProfile;
use App\Models\Project;

/**
 * Bound by default (see AppServiceProvider). Returning null here is not
 * an error state — MatchScoringService's fallback heuristic is a fully
 * legitimate scoring path, not a degraded one, so this class doesn't log
 * anything. It only exists so callers never have to null-check "is AI
 * even configured" themselves.
 */
class NullSemanticAnalyzer implements AiSemanticAnalyzer
{
    public function analyze(Project $project, InvestorProfile $investor): ?MatchAiResult
    {
        return null;
    }
}
