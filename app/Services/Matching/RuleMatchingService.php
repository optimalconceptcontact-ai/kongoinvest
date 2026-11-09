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
     * Financement — compatibilité combinée ticket & instruments (Catalogue dynamique & financements mixtes).
     * Combine à parts égales (50% / 50%) le recouvrement de ticket et l'alignement des instruments financiers.
     */
    public function scoreFunding(Project $project, InvestorProfile $investor): int
    {
        // 1. Calcul du recouvrement de ticket financier
        $ticketScore = 50;
        if ($investor->ticket_min_minor || $investor->ticket_max_minor) {
            // Devises différentes : les montants ne sont pas comparables
            // sans conversion. On reste neutre plutôt que de pénaliser
            // un dossier qui pourrait parfaitement convenir au taux du
            // jour, et on n'agrège pas l'instrument dans ce cas.
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
                $ticketScore = 0; // Aucun chevauchement
            } else {
                $investorRange = max(1, $investorMax - $investorMin);
                $overlapSize = $overlapHigh - $overlapLow;
                $ticketScore = (int) round(min(100, ($overlapSize / $investorRange) * 100));
            }
        }

        // 2. Calcul de la compatibilité des instruments & types de financement
        $instrumentScore = $this->scoreInstrumentCompatibility($project, $investor);

        // Si le ticket ne chevauche absolument pas, le score financier global reste éliminatoire (0)
        if ($ticketScore === 0) {
            return 0;
        }

        return (int) round(($ticketScore * 0.5) + ($instrumentScore * 0.5));
    }

    /**
     * Évalue la compatibilité des instruments financiers et types de financement.
     */
    public function scoreInstrumentCompatibility(Project $project, InvestorProfile $investor): int
    {
        $investorInstruments = $investor->preferredInstruments;

        // Cas A : L'investisseur a configuré des instruments précis
        if ($investorInstruments->isNotEmpty()) {
            $investorInstIds = $investorInstruments->pluck('id');

            // Vérifier via la demande de financement structurée
            $components = $project->latestFinancingRequest?->components;
            if ($components && $components->isNotEmpty()) {
                $projectInstIds = $components->pluck('financial_instrument_id');
                $overlapCount = $projectInstIds->intersect($investorInstIds)->count();

                if ($projectInstIds->isNotEmpty()) {
                    return (int) round(($overlapCount / $projectInstIds->count()) * 100);
                }
            }

            // Fallback si pas de components : comparer catégories des instruments de l'investisseur aux types legacy du projet
            $investorCategories = $investorInstruments->map(fn ($i) => match ($i->instrument_category) {
                'hybrid' => 'convertible_note',
                default => $i->instrument_category,
            })->unique();

            $projectTypes = collect($project->investment_types ?? []);
            if ($projectTypes->isNotEmpty()) {
                $matches = $projectTypes->intersect($investorCategories)->count();

                return (int) round(($matches / $projectTypes->count()) * 100);
            }
        }

        // Cas B : L'investisseur utilise l'Enum unitaire legacy (funding_type)
        if ($investor->funding_type) {
            $invType = $investor->funding_type->value;
            $projectTypes = collect($project->investment_types ?? []);

            // Vérifier aussi les instruments de la demande structurée
            $components = $project->latestFinancingRequest?->components;
            if ($components && $components->isNotEmpty()) {
                $hasMatch = $components->contains(function ($c) use ($invType) {
                    $cat = $c->instrument?->instrument_category;
                    $mapped = match ($cat) {
                        'hybrid' => 'convertible_note',
                        default => $cat,
                    };

                    return $mapped === $invType;
                });

                if ($hasMatch) {
                    return 100;
                }
            }

            if ($projectTypes->contains($invType)) {
                return 100;
            }

            // Mismatch évident (ex: investisseur 100% dette vs projet 100% equity)
            return 10;
        }

        // Aucun critère d'instrument renseigné par l'investisseur : neutre positif
        return 75;
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
