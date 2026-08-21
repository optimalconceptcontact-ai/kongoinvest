<?php

namespace App\Services\Matching;

use App\Models\InvestorProfile;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Implements the AI architecture from spec §56: this is the only class
 * that talks to the AI provider directly. Controllers/Jobs never call
 * Anthropic's API themselves — they go through MatchScoringService,
 * which depends on the AiSemanticAnalyzer *interface*, not this class.
 *
 * Every path through analyze() either returns a validated MatchAiResult
 * or null. It never throws — a slow/failed/malformed AI response must
 * degrade the match score calculation, not break the page (spec §113).
 */
class AnthropicSemanticAnalyzer implements AiSemanticAnalyzer
{
    public function analyze(Project $project, InvestorProfile $investor): ?MatchAiResult
    {
        $apiKey = config('services.anthropic.api_key');

        if (blank($apiKey)) {
            return null; // not configured — not an error, just unavailable
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
                ->timeout(20)
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => config('services.anthropic.model', 'claude-sonnet-4-6'),
                    'max_tokens' => 600,
                    'system' => $this->systemPrompt(),
                    'messages' => [
                        ['role' => 'user', 'content' => $this->buildPrompt($project, $investor)],
                    ],
                ]);

            if ($response->failed()) {
                Log::warning('AI matching analysis failed', [
                    'status' => $response->status(),
                    'project_id' => $project->id,
                    'investor_profile_id' => $investor->id,
                ]);

                return null;
            }

            return $this->parse($response->json());
        } catch (\Throwable $e) {
            Log::warning('AI matching analysis threw', [
                'message' => $e->getMessage(),
                'project_id' => $project->id,
                'investor_profile_id' => $investor->id,
            ]);

            return null;
        }
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
            Tu es un analyste en investissement qui évalue la compatibilité entre
            un projet entrepreneurial en RDC et la thèse d'investissement d'un
            investisseur. Réponds UNIQUEMENT avec un objet JSON valide, sans
            markdown, sans texte avant/après, au format exact :
            {"business_model_score": <0-100>, "traction_score": <0-100>, "impact_score": <0-100>, "rationale": "<2-3 phrases>"}

            Distingue clairement les faits fournis des suppositions dans ton
            raisonnement interne, mais la rationale doit rester concise. Ne
            garantis jamais un résultat, un rendement ou un financement — tu
            évalues une compatibilité informative uniquement (spec §148).
            PROMPT;
    }

    private function buildPrompt(Project $project, InvestorProfile $investor): string
    {
        return sprintf(
            "PROJET\nModèle économique: %s\nTraction: %s\nImpact: %s\n\nTHÈSE D'INVESTISSEUR\n%s\n\nÉvalue la compatibilité sur les 3 critères demandés.",
            $project->business_model ?: 'Non renseigné',
            $project->traction ?: 'Non renseigné',
            $project->impact ?: 'Non renseigné',
            $investor->investment_thesis ?: 'Non renseignée',
        );
    }

    private function parse(array $json): ?MatchAiResult
    {
        $text = collect($json['content'] ?? [])
            ->firstWhere('type', 'text')['text'] ?? null;

        if (! $text) {
            return null;
        }

        $decoded = json_decode(trim($text), true);

        if (! is_array($decoded) || ! isset($decoded['business_model_score'], $decoded['traction_score'], $decoded['impact_score'])) {
            Log::warning('AI matching analysis returned unparseable JSON', ['raw' => $text]);

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
