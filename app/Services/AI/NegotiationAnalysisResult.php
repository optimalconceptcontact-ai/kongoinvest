<?php

namespace App\Services\Ai;

use App\Enums\FinancingComponentType;
use App\Enums\NegotiationTermStatus;
use App\Enums\TermCategory;
use App\Enums\TermKind;
use Illuminate\Support\Str;

class NegotiationAnalysisResult
{
    /**
     * @param  string  $summary  synthèse plafonnée, jamais persistée brute
     * @param  list<array<string, mixed>>  $terms  conditions revalidées (enums contraints, valeurs épurées)
     * @param  list<array{library_slug: string, recommended_variant: ?string, rationale: string}>  $clauseRecommendations
     */
    public function __construct(
        public readonly string $summary,
        public readonly array $terms,
        public readonly array $clauseRecommendations,
        public readonly int $tokensUsed,
    ) {}

    /**
     * Builds a result from model-controlled decoded output, enforcing the
     * server-side invariants the model cannot be trusted with: enum keys
     * are constrained to actual enum values, free keys are normalized to
     * stable snake_case slugs, and nested payloads are reduced to known
     * scalar shapes. Anything unusable is dropped, never fabricated.
     *
     * @param  array<string, mixed>  $decoded
     */
    public static function fromDecoded(array $decoded, int $tokensUsed): ?self
    {
        $categories = collect(TermCategory::cases())->map(fn ($case) => $case->value);
        $kinds = collect(TermKind::cases())->map(fn ($case) => $case->value);
        $statuses = collect(NegotiationTermStatus::cases())->map(fn ($case) => $case->value)
            ->reject(fn ($value) => $value === NegotiationTermStatus::Formalized->value); // la formalisation relève d'un document humain, jamais de l'IA
        $instruments = collect(FinancingComponentType::cases())->map(fn ($case) => $case->value);
        $text = fn ($value, int $cap = 5000) => mb_substr(trim((string) ($value ?? '')), 0, $cap);

        // Conditions extraites : chaque ligne est revalidée valeur par
        // valeur ; une ligne sans clé ni libellé est jetée.
        $terms = collect(is_array($decoded['terms'] ?? null) ? $decoded['terms'] : [])
            ->filter(fn ($row) => is_array($row) && filled($row['subject'] ?? null) && filled($row['label'] ?? null))
            ->slice(0, 100)
            ->values()
            ->map(function ($row) use ($categories, $kinds, $statuses, $instruments, $text) {
                $subject = Str::snake(mb_strtolower($text($row['subject'], 80)));

                if (! preg_match('/^[a-z][a-z0-9_]{2,63}$/', $subject)) {
                    $subject = 'condition_non_normee';
                }

                $category = $categories->contains($row['category'] ?? null) ? $row['category'] : TermCategory::Other->value;
                $kind = $kinds->contains($row['kind'] ?? null) ? $row['kind'] : TermKind::Other->value;

                // Statut contraint : jamais « formalized » — une
                // qualification hors échelle retombe en « à clarifier »
                // plutôt qu'en engagement (règle §16, r.3).
                $status = $statuses->contains($row['status'] ?? null)
                    ? $row['status']
                    : NegotiationTermStatus::ToClarify->value;

                $instrument = $instruments->contains($row['instrument'] ?? null)
                    ? $row['instrument']
                    : null;

                return [
                    'subject' => $subject,
                    'label' => $text($row['label'], 200),
                    'category' => $category,
                    'kind' => $kind,
                    'instrument' => $instrument,
                    'value' => self::normalizeValue(is_array($row['value'] ?? null) ? $row['value'] : []),
                    'display_value' => $text($row['display_value'], 500),
                    'status' => $status,
                    'source_index' => (is_int($row['source_index'] ?? null) && $row['source_index'] >= 1 && $row['source_index'] <= 10_000)
                        ? $row['source_index']
                        : null,
                ];
            })
            ->all();

        // Re-clé en liste strictement séquentielle : garanties de type
        // pour les consommateurs du DTO (list, jamais un tableau
        // associatif épars après filtrage).
        $terms = array_values($terms);

        // Recommandations de clauses : slug strict (bibliothèque) et
        // variante courte — la sélection ne cite JAMAIS un texte de
        // modèle IA (prompt §5).
        $clauseSlug = fn ($value) => preg_match('/^[a-z0-9][a-z0-9-]{1,79}$/', mb_strtolower($text($value, 80)))
            ? mb_strtolower($text($value, 80))
            : null;

        $clauses = collect(is_array($decoded['clause_recommendations'] ?? null) ? $decoded['clause_recommendations'] : [])
            ->filter(fn ($row) => is_array($row) && filled($row['library_slug'] ?? null))
            ->slice(0, 20)
            ->values()
            ->map(fn ($row) => [
                'library_slug' => $clauseSlug($row['library_slug']),
                'recommended_variant' => isset($row['recommended_variant']) && filled($row['recommended_variant'])
                    ? mb_substr((string) $row['recommended_variant'], 0, 64)
                    : null,
                'rationale' => $text($row['rationale'] ?? '', 2000),
            ])
            ->filter(fn ($row) => $row['library_slug'] !== null)
            ->all();

        $clauses = array_values($clauses);

        $summary = $text($decoded['summary'] ?? '');

        // Une synthèse vide sans conditions n'est pas une analyse.
        if ($summary === '' && $terms === []) {
            return null;
        }

        return new self(
            summary: $summary !== '' ? $summary : 'Parcours de négociation consultable dans le détail des conditions.',
            terms: $terms,
            clauseRecommendations: $clauses,
            tokensUsed: max(0, $tokensUsed),
        );
    }

    /**
     * Le modèle ne peut déposer que des scalaires connus dans la
     * valeur normalisée — rien de transformé en code, rien de
     * gigantesque, rien d'inexploitable.
     *
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    private static function normalizeValue(array $value): array
    {
        $cleaned = [
            'amount_minor' => isset($value['amount_minor']) && is_numeric($value['amount_minor']) ? max(0, (int) $value['amount_minor']) : null,
            'bps' => isset($value['bps']) && is_numeric($value['bps']) ? min(10_000, max(0, (int) $value['bps'])) : null,
            'months' => isset($value['months']) && is_numeric($value['months']) ? min(1200, max(0, (int) $value['months'])) : null,
            'text' => isset($value['text']) && filled($value['text'])
                ? mb_substr(trim((string) $value['text']), 0, 500)
                : null,
        ];

        return array_filter($cleaned, fn ($item) => $item !== null);
    }
}
