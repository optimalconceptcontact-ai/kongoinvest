<?php

namespace App\Ai\Agents;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Spec §50-52, §148 — a senior VC-style analyst that scores a project
 * 0-100 and produces the structured report rendered on the entrepreneur
 * and investor project pages.
 *
 * This class only knows how to talk to the model. It never touches the
 * database, the quota, or the audit log — LaravelAiProjectAnalysisProvider
 * calls it, ProjectAnalysisService persists the result. The structured
 * schema below is what forces the model to answer in the exact shape
 * ProjectAnalysisResult expects.
 */
class ProjectAnalysisAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(public Project $project) {}

    public function instructions(): string
    {
        return <<<'PROMPT'
            Tu es un analyste senior en capital-investissement qui évalue des
            projets entrepreneuriaux pour des investisseurs internationaux.

            Règles absolues (spec §148) :
            - Tu n'es qu'un outil d'aide à la décision. Tu ne garantis JAMAIS
              un rendement, un financement, ou la réussite d'un projet.
            - Distingue toujours les FAITS fournis des SUPPOSITIONS que tu
              formules par nécessité.
            - N'invente aucune donnée (chiffre, client, contrat) qui n'est
              pas dans les informations fournies — signale plutôt l'absence
              d'information comme un risque ou une question de due diligence.
            - Le score que tu donnes est une estimation, jamais une vérité
              absolue.
            - Rédige toutes les valeurs textuelles en français.
            PROMPT;
    }

    /**
     * Free-text context block handed to the model as the user prompt.
     */
    public function context(): string
    {
        $project = $this->project;

        $team = collect($project->team ?? [])
            ->map(fn (array $m) => trim(($m['full_name'] ?? '').(filled($m['role'] ?? null) ? ' ('.$m['role'].')' : '')))
            ->filter()
            ->implode(', ');

        $fields = [
            'Titre' => $project->title,
            'Description' => $project->description,
            'Résumé exécutif' => $project->executive_summary,
            'Problème' => $project->problem,
            'Solution' => $project->solution,
            'Modèle économique' => $project->business_model,
            'Marché' => $project->market,
            'Avantage concurrentiel' => $project->competitive_advantage,
            'Équipe' => $team ?: null,
            'Traction' => $project->traction,
            'Informations financières' => $project->financial_information,
            'Objectif de financement' => $project->funding_target_minor
                ? number_format($project->funding_target_minor / 100, 0).' '.$project->funding_currency
                : null,
            'Impact' => $project->impact,
            'Risques déclarés' => $project->risks,
        ];

        $lines = collect($fields)
            ->map(fn ($value, $label) => "{$label}: ".(filled($value) ? $value : 'Non renseigné'))
            ->implode("\n");

        return "Analyse ce projet entrepreneurial basé en RDC :\n\n{$lines}";
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $sentence = fn () => $schema->string()->required();
        $bullets = fn () => $schema->array()->items($schema->string())->required();

        return [
            'investment_score' => $schema->integer()->min(0)->max(100)->required(),
            'sub_scores' => $schema->object([
                'market' => $schema->integer()->min(0)->max(100)->required(),
                'team' => $schema->integer()->min(0)->max(100)->required(),
                'traction' => $schema->integer()->min(0)->max(100)->required(),
                'business_model' => $schema->integer()->min(0)->max(100)->required(),
                'scalability' => $schema->integer()->min(0)->max(100)->required(),
                'risk' => $schema->integer()->min(0)->max(100)->required(),
                'financial_potential' => $schema->integer()->min(0)->max(100)->required(),
            ])->required(),
            'executive_summary' => $sentence(),
            'business_model_assessment' => $sentence(),
            'market_potential' => $sentence(),
            'problem_solution_fit' => $sentence(),
            'competitive_advantage' => $sentence(),
            'team_assessment' => $sentence(),
            'traction_assessment' => $sentence(),
            'financial_potential' => $sentence(),
            'scalability' => $sentence(),
            'strengths' => $bullets(),
            'weaknesses' => $bullets(),
            'risks' => $bullets(),
            'red_flags' => $bullets(),
            'due_diligence_questions' => $bullets(),
            'funding_assessment' => $sentence(),
            'recommendation' => $sentence(),
        ];
    }
}
