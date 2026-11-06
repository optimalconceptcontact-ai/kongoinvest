<?php

namespace App\Ai\Agents;

use App\Models\InvestorProfile;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Spec §23, §56 — scores the 3 narrative match criteria (business model,
 * traction, impact) by reading a project's free-text fields against an
 * investor's stated thesis.
 *
 * This is the AI half of the hybrid matching architecture: it only runs
 * when config('matching.ai_enabled') is on, and MatchScoringService
 * always keeps its deterministic completeness heuristic as the fallback,
 * so a missing or malformed answer degrades the score, never the page.
 */
class MatchCompatibilityAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        public Project $project,
        public InvestorProfile $investor,
    ) {}

    public function instructions(): string
    {
        return <<<'PROMPT'
            Tu es un analyste en investissement qui évalue la compatibilité
            entre un projet entrepreneurial en RDC et la thèse
            d'investissement d'un investisseur.

            Distingue clairement les faits fournis des suppositions dans ton
            raisonnement, mais garde la rationale concise (2-3 phrases). Ne
            garantis jamais un résultat, un rendement ou un financement — tu
            évalues une compatibilité informative uniquement (spec §148).
            Rédige la rationale en français.
            PROMPT;
    }

    /**
     * Free-text context block handed to the model as the user prompt.
     */
    public function context(): string
    {
        return sprintf(
            "PROJET\nModèle économique: %s\nTraction: %s\nImpact: %s\n\nTHÈSE D'INVESTISSEUR\n%s\n\nÉvalue la compatibilité sur les 3 critères demandés.",
            $this->project->business_model ?: 'Non renseigné',
            $this->project->traction ?: 'Non renseigné',
            $this->project->impact ?: 'Non renseigné',
            $this->investor->investment_thesis ?: 'Non renseignée',
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'business_model_score' => $schema->integer()->min(0)->max(100)->required(),
            'traction_score' => $schema->integer()->min(0)->max(100)->required(),
            'impact_score' => $schema->integer()->min(0)->max(100)->required(),
            'rationale' => $schema->string()->required(),
        ];
    }
}
