<?php

namespace App\Services\Ai\Providers;

use App\Contracts\ContractDraftProviderInterface;
use App\Enums\FundingType;
use App\Models\DealRoom;
use App\Services\Ai\ContractDraftResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnthropicContractDraftProvider implements ContractDraftProviderInterface
{
    public function draft(DealRoom $dealRoom, FundingType $investmentType, string $conversationTranscript): ?ContractDraftResult
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
                ->timeout(60)
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => config('services.anthropic.model', 'claude-sonnet-4-6'),
                    'max_tokens' => 4000,
                    'system' => $this->systemPrompt(),
                    'messages' => [
                        ['role' => 'user', 'content' => $this->buildPrompt($dealRoom, $investmentType, $conversationTranscript)],
                    ],
                ]);

            if ($response->failed()) {
                Log::warning('AI contract draft failed', ['status' => $response->status(), 'deal_room_id' => $dealRoom->id]);

                return null;
            }

            return $this->parse($response->json(), $investmentType);
        } catch (\Throwable $e) {
            Log::warning('AI contract draft threw', ['message' => $e->getMessage(), 'deal_room_id' => $dealRoom->id]);

            return null;
        }
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
            Tu es un juriste d'affaires senior spécialisé en droit des
            sociétés et en financement d'entreprises, agissant comme
            rédacteur de projet de contrat pour une plateforme de mise en
            relation entre entrepreneurs et investisseurs en République
            Démocratique du Congo.

            Règles absolues :
            - Applique le cadre juridique le plus rigoureux disponible :
              le droit OHADA (Actes uniformes relatifs au droit commercial
              général et au droit des sociétés commerciales et du GIE,
              dont la RDC est membre depuis 2012), complété par le droit
              congolais des obligations.
            - Le contrat doit protéger juridiquement les DEUX parties de
              façon équilibrée — jamais uniquement l'investisseur ni
              uniquement l'entrepreneur.
            - N'invente aucun fait (montant, échéance, condition) qui n'a
              pas été explicitement mentionné et accepté dans la
              conversation fournie ou dans les données du projet. Si un
              élément essentiel manque (ex: montant jamais discuté),
              indique une valeur raisonnable dérivée de l'objectif de
              financement du projet et signale-le comme tel dans le
              résumé plutôt que d'inventer un chiffre précis non appuyé.
            - Ce document reste un projet de contrat généré automatiquement,
              pas un acte juridique validé — n'affirme jamais le contraire.
            - Rédige en français.

            Réponds UNIQUEMENT avec un objet JSON valide, sans markdown,
            sans texte avant ou après, au format exact :
            {
              "amount_minor": <entier, montant en centimes/subdivision mineure>,
              "currency": "<code devise ISO 3 lettres>",
              "summary": "<résumé de l'accord en 3-5 phrases>",
              "obligations_entrepreneur": ["<obligation précise>", "..."],
              "obligations_investor": ["<obligation précise>", "..."],
              "conditions_precedent": ["<condition suspensive>", "..."],
              "milestones": ["<jalon avec échéance si connue>", "..."],
              "termination_clauses": "<conditions de résiliation, 2-4 phrases>",
              "dispute_resolution": "<mécanisme de règlement des différends, 2-3 phrases>",
              "governing_law": "<droit applicable>",
              "deadline": "<date AAAA-MM-JJ ou null si non déterminable>"
            }
            PROMPT;
    }

    private function buildPrompt(DealRoom $dealRoom, FundingType $investmentType, string $conversationTranscript): string
    {
        $project = $dealRoom->project;

        $fields = [
            'Type de financement retenu par l\'investisseur' => $investmentType->label(),
            'Types de financement envisagés par l\'entrepreneur' => collect($project->investment_types ?? [])
                ->map(fn ($t) => FundingType::from($t)->label())->implode(', '),
            'Projet' => $project->title,
            'Description' => $project->description,
            'Objectif de financement' => $project->funding_target_minor
                ? number_format($project->funding_target_minor / 100, 0).' '.$project->funding_currency
                : 'Non renseigné',
            'Fourchette' => ($project->funding_min_minor || $project->funding_max_minor)
                ? number_format(($project->funding_min_minor ?? 0) / 100, 0).' - '.number_format(($project->funding_max_minor ?? 0) / 100, 0).' '.$project->funding_currency
                : 'Non renseignée',
            'Entrepreneur' => $dealRoom->entrepreneur->name,
            'Investisseur' => $dealRoom->investor->name,
        ];

        $lines = collect($fields)
            ->map(fn ($value, $label) => "{$label}: ".(filled($value) ? $value : 'Non renseigné'))
            ->implode("\n");

        return "Rédige les termes d'un projet de contrat d'investissement à partir de ces informations :\n\n{$lines}\n\n"
            ."Conversation entre les deux parties dans le Deal Room (base tes montants et conditions sur ce qui y a été discuté et accepté) :\n\n{$conversationTranscript}";
    }

    private function parse(array $json, FundingType $investmentType): ?ContractDraftResult
    {
        $text = collect($json['content'] ?? [])->firstWhere('type', 'text')['text'] ?? null;

        if (! $text) {
            return null;
        }

        $decoded = json_decode(trim($text), true);

        if (! is_array($decoded) || ! isset($decoded['amount_minor'], $decoded['summary'])) {
            Log::warning('AI contract draft returned unparseable JSON', ['raw' => $text]);

            return null;
        }

        $strings = fn ($v) => is_array($v) ? array_values(array_map('strval', $v)) : [];

        return new ContractDraftResult(
            investmentType: $investmentType->value,
            amountMinor: max(0, (int) $decoded['amount_minor']),
            currency: strtoupper((string) ($decoded['currency'] ?? 'USD')),
            summary: (string) ($decoded['summary'] ?? ''),
            obligationsEntrepreneur: $strings($decoded['obligations_entrepreneur'] ?? []),
            obligationsInvestor: $strings($decoded['obligations_investor'] ?? []),
            conditionsPrecedent: $strings($decoded['conditions_precedent'] ?? []),
            milestones: $strings($decoded['milestones'] ?? []),
            terminationClauses: (string) ($decoded['termination_clauses'] ?? ''),
            disputeResolution: (string) ($decoded['dispute_resolution'] ?? ''),
            governingLaw: (string) ($decoded['governing_law'] ?? 'Droit OHADA et droit congolais des obligations'),
            deadline: filled($decoded['deadline'] ?? null) ? (string) $decoded['deadline'] : null,
            tokensUsed: ($json['usage']['input_tokens'] ?? 0) + ($json['usage']['output_tokens'] ?? 0),
        );
    }
}
