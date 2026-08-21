<?php

namespace App\Contracts;

use App\Models\Project;
use App\Services\Ai\ProjectAnalysisResult;

/**
 * Spec §56: React never calls an AI provider directly — every request
 * goes React -> Inertia -> Controller -> this interface -> provider ->
 * validated structured output -> DB -> Inertia -> React. Same
 * "interface first, Null provider default" architecture as Phase 5's
 * AiSemanticAnalyzer, applied here to full project analysis instead of
 * match scoring.
 *
 * Unlike matching, there is deliberately NO rules-based fallback for a
 * failed/unconfigured call — spec §113 says a failure must show a clean
 * retry state, not break the page, but writing a rules-based
 * "Investment Score" would be presenting a fake AI judgment as real one
 * (the opposite of what §148 requires). analyze() returning null means
 * "no analysis exists yet," never "here's a substitute."
 */
interface ProjectAnalysisProviderInterface
{
    public function analyze(Project $project): ?ProjectAnalysisResult;
}
