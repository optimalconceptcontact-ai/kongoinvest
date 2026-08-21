<?php

namespace App\Services\Matching;

use App\Models\InvestorProfile;
use App\Models\Project;

interface AiSemanticAnalyzer
{
    /**
     * Scores the 3 narrative criteria (business model, traction, impact)
     * by reading the project's free-text fields against the investor's
     * stated thesis. Must return null — never throw — on any failure;
     * MatchScoringService treats null as "fall back to the neutral
     * completeness heuristic," per spec §113 (an AI failure must never
     * break the page).
     */
    public function analyze(Project $project, InvestorProfile $investor): ?MatchAiResult;
}
