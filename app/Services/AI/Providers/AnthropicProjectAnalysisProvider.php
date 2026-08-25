<?php

namespace App\Services\Ai\Providers;

use App\Contracts\ProjectAnalysisProviderInterface;
use App\Models\Project;
use App\Services\Ai\ProjectAnalysisResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnthropicProjectAnalysisProvider implements ProjectAnalysisProviderInterface
{
    public function analyze(Project $project): ?ProjectAnalysisResult
    {
        $apiKey = config('services.anthropic.api_key');

        if (blank($apiKey)) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
                ->timeout(45)
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => config('services.anthropic.model', 'claude-sonnet-4-6'),
                    'max_tokens' => 3000,
                    'system' => $this->systemPrompt(),
                    'messages' => [
                        ['role' => 'user', 'content' => $this->buildPrompt($project)],
                    ],
                ]);

            if ($response->failed()) {
                Log::warning('AI project analysis failed', ['status' => $response->status(), 'project_id' => $project->id]);

                return null;
            }

            return $this->parse($response->json());
        } catch (\Throwable $e) {
            Log::warning('AI project analysis threw', ['message' => $e->getMessage(), 'project_id' => $project->id]);

            return null;
        }
    }

    private function systemPrompt(): string
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

            Réponds UNIQUEMENT avec un objet JSON valide, sans markdown, sans
            texte avant ou après, au format exact :
            {
              "investment_score": <0-100>,
              "sub_scores": {"market": <0-100>, "team": <0-100>, "traction": <0-100>, "business_model": <0-100>, "scalability": <0-100>, "risk": <0-100>, "financial_potential": <0-100>},
              "executive_summary": "<3-4 phrases>",
              "business_model_assessment": "<2-4 phrases>",
              "market_potential": "<2-4 phrases>",
              "problem_solution_fit": "<2-4 phrases>",
              "competitive_advantage": "<2-4 phrases>",
              "team_assessment": "<2-4 phrases>",
              "traction_assessment": "<2-4 phrases>",
              "financial_potential": "<2-4 phrases>",
              "scalability": "<2-4 phrases>",
              "strengths": ["<point court>", "..."],
              "weaknesses": ["<point court>", "..."],
              "risks": ["<point court>", "..."],
              "red_flags": ["<point court, ou tableau vide si aucun>"],
              "due_diligence_questions": ["<question précise>", "..."],
              "funding_assessment": "<le besoin de financement semble-t-il cohérent ? 2-3 phrases>",
              "recommendation": "<synthèse équilibrée, jamais une instruction d'investir ou non — 2-3 phrases>"
            }
            PROMPT;
    }

    private function buildPrompt(Project $project): string
    {
        $fields = [
            'Titre' => $project->title,
            'Description' => $project->description,
            'Résumé exécutif' => $project->executive_summary,
            'Problème' => $project->problem,
            'Solution' => $project->solution,
            'Modèle économique' => $project->business_model,
            'Marché' => $project->market,
            'Avantage concurrentiel' => $project->competitive_advantage,
            'Équipe' => collect($project->team ?? [])
                ->map(fn (array $m) => trim(($m['full_name'] ?? '').(filled($m['role'] ?? null) ? ' ('.$m['role'].')' : '')))
                ->filter()
                ->implode(', ') ?: null,
            'Traction' => $project->traction,
            'Informations financières' => $project->financial_information,
            'Objectif de financement' => $project->funding_target_minor
                ? number_format($project->funding_target_minor / 100, 0).' '.$project->funding_currency
                : 'Non renseigné',
            'Impact' => $project->impact,
            'Risques déclarés' => $project->risks,
        ];

        $lines = collect($fields)
            ->map(fn ($value, $label) => "{$label}: ".(filled($value) ? $value : 'Non renseigné'))
            ->implode("\n");

        return "Analyse ce projet entrepreneurial basé en RDC :\n\n{$lines}";
    }

    private function parse(array $json): ?ProjectAnalysisResult
    {
        $text = collect($json['content'] ?? [])->firstWhere('type', 'text')['text'] ?? null;

        if (! $text) {
            return null;
        }

        $decoded = json_decode(trim($text), true);

        if (! is_array($decoded) || ! isset($decoded['investment_score'], $decoded['sub_scores'])) {
            Log::warning('AI project analysis returned unparseable JSON', ['raw' => $text]);

            return null;
        }

        $clamp = fn ($v) => max(0, min(100, (int) $v));
        $strings = fn ($v) => is_array($v) ? array_values(array_map('strval', $v)) : [];

        return new ProjectAnalysisResult(
            investmentScore: $clamp($decoded['investment_score']),
            subScores: [
                'market' => $clamp($decoded['sub_scores']['market'] ?? 50),
                'team' => $clamp($decoded['sub_scores']['team'] ?? 50),
                'traction' => $clamp($decoded['sub_scores']['traction'] ?? 50),
                'businessModel' => $clamp($decoded['sub_scores']['business_model'] ?? 50),
                'scalability' => $clamp($decoded['sub_scores']['scalability'] ?? 50),
                'risk' => $clamp($decoded['sub_scores']['risk'] ?? 50),
                'financialPotential' => $clamp($decoded['sub_scores']['financial_potential'] ?? 50),
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
            tokensUsed: ($json['usage']['input_tokens'] ?? 0) + ($json['usage']['output_tokens'] ?? 0),
        );
    }
}
