<?php

namespace App\Services\Matching;

use App\Enums\RiskAppetite;
use App\Models\InvestorProfile;
use App\Models\Project;

class RuleMatchingService
{
    /**
     * Sector overlap — spec §17/§23. An investor may hold several
     * sectors; any intersection counts, scaled by how much of the
     * project's own sector set is covered (a project tagged in 3
     * sectors that only overlaps 1 shouldn't score as high as a project
     * tagged in exactly the investor's sector).
     */
    public function scoreSector(Project $project, InvestorProfile $investor): int
    {
        $projectSectorIds = $project->sectors->pluck('id');
        $investorSectorIds = $investor->sectors->pluck('id');

        if ($projectSectorIds->isEmpty() || $investorSectorIds->isEmpty()) {
            return 0;
        }

        $overlap = $projectSectorIds->intersect($investorSectorIds)->count();

        return (int) round(($overlap / $projectSectorIds->count()) * 100);
    }

    /** Same overlap logic as sector, applied to stages (spec §18/§23). */
    public function scoreStage(Project $project, InvestorProfile $investor): int
    {
        $projectStageIds = $project->stages->pluck('id');
        $investorStageIds = $investor->stages->pluck('id');

        if ($projectStageIds->isEmpty() || $investorStageIds->isEmpty()) {
            return 0;
        }

        $overlap = $projectStageIds->intersect($investorStageIds)->count();

        return (int) round(($overlap / $projectStageIds->count()) * 100);
    }

    /**
     * Ticket-size compatibility — how well the investor's [min,max]
     * ticket range overlaps the project's [min,max] funding ask. Ignores
     * currency conversion for now (Phase 10+ will need real FX per
     * spec §126 — never a hardcoded rate); same-currency comparisons
     * only until then, mismatched currencies score neutral (50).
     */
    public function scoreFunding(Project $project, InvestorProfile $investor): int
    {
        if (! $investor->ticket_min_minor && ! $investor->ticket_max_minor) {
            return 50; // investor hasn't set a ticket range — neutral, not a penalty
        }

        if ($investor->ticket_currency !== $project->funding_currency) {
            return 50;
        }

        $investorMin = $investor->ticket_min_minor ?? 0;
        $investorMax = $investor->ticket_max_minor ?? PHP_INT_MAX;
        $projectMin = $project->funding_min_minor ?? 0;
        $projectMax = $project->funding_max_minor ?? $project->funding_target_minor ?? PHP_INT_MAX;

        $overlapLow = max($investorMin, $projectMin);
        $overlapHigh = min($investorMax, $projectMax);

        if ($overlapLow > $overlapHigh) {
            return 0; // ranges don't intersect at all
        }

        // Score how much of the investor's range the project actually
        // uses — a project asking for exactly the investor's range
        // scores 100; one that only touches the edge scores lower.
        $investorRange = max(1, $investorMax - $investorMin);
        $overlapSize = $overlapHigh - $overlapLow;

        return (int) round(min(100, ($overlapSize / $investorRange) * 100));
    }

    /**
     * Geography — investors declare preferred country codes (spec §16).
     * An empty preference list means "anywhere," which scores as a full
     * match for a DRC-based project rather than a penalty for not
     * specifying.
     */
    public function scoreGeography(Project $project, InvestorProfile $investor): int
    {
        $preferred = $investor->preferred_geographies ?? [];

        if (empty($preferred)) {
            return 100;
        }

        return in_array($project->country_code, $preferred, true) ? 100 : 20;
    }

    /**
     * Risk appetite vs. project stage, as a rough proxy for project risk
     * (the Project model has no explicit risk score — Phase 12's AI
     * project analysis may add one later). Early-stage projects are
     * treated as higher risk; conservative investors score better
     * against later-stage projects and vice versa.
     */
    public function scoreRisk(Project $project, InvestorProfile $investor): int
    {
        if (! $investor->risk_appetite) {
            return 50;
        }

        $earlyStageKeys = ['idea', 'pre_seed', 'seed'];
        $isEarlyStage = $project->stages->pluck('key')->intersect($earlyStageKeys)->isNotEmpty();

        return match ($investor->risk_appetite) {
            RiskAppetite::Aggressive => $isEarlyStage ? 100 : 70,
            RiskAppetite::Moderate => 70,
            RiskAppetite::Conservative => $isEarlyStage ? 30 : 90,
        };
    }
}
