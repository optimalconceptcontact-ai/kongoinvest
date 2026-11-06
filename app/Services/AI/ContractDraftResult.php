<?php

namespace App\Services\Ai;

use App\Enums\FundingType;
use App\Models\Project;

class ContractDraftResult
{
    /**
     * @param  string[]  $obligationsEntrepreneur
     * @param  string[]  $obligationsInvestor
     * @param  string[]  $conditionsPrecedent
     * @param  string[]  $milestones
     */
    public function __construct(
        public string $investmentType,
        public int $amountMinor,
        public string $currency,
        public string $summary,
        public array $obligationsEntrepreneur,
        public array $obligationsInvestor,
        public array $conditionsPrecedent,
        public array $milestones,
        public string $terminationClauses,
        public string $disputeResolution,
        public string $governingLaw,
        public ?string $deadline,
        public int $tokensUsed,
    ) {}

    /**
     * Builds a result from model-controlled decoded output, enforcing the
     * server-side invariants the model cannot be trusted with: amounts are
     * clamped to the project's declared funding range, the currency is
     * pinned to the project's currency, and the deadline must be a real
     * calendar date. Returns null when the payload is unusable.
     *
     * @param  array<string, mixed>  $decoded
     */
    public static function fromDecoded(array $decoded, FundingType $investmentType, ?Project $project, int $tokensUsed): ?self
    {
        if (! isset($decoded['amount_minor'], $decoded['summary'])) {
            return null;
        }

        $amountMinor = max(0, (int) $decoded['amount_minor']);
        $fundingMax = $project?->funding_max_minor;
        if (is_int($fundingMax) && $fundingMax > 0 && $amountMinor > $fundingMax) {
            $amountMinor = $fundingMax;
        }

        $currency = strtoupper(trim((string) ($decoded['currency'] ?? '')));
        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            $currency = $project?->funding_currency ?? 'USD';
        }
        if ($project?->funding_currency && $currency !== $project->funding_currency) {
            $currency = $project->funding_currency;
        }

        $deadline = null;
        if (is_string($decoded['deadline'] ?? null)
            && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $decoded['deadline'], $matches)
            && checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            $deadline = $matches[0];
        }

        $strings = fn ($value) => is_array($value)
            ? array_values(array_map(
                fn ($item) => mb_substr((string) $item, 0, 5000),
                array_slice($value, 0, 100)
            ))
            : [];
        $text = fn ($value) => mb_substr((string) ($value ?? ''), 0, 10000);

        return new self(
            investmentType: $investmentType->value,
            amountMinor: $amountMinor,
            currency: $currency,
            summary: $text($decoded['summary']),
            obligationsEntrepreneur: $strings($decoded['obligations_entrepreneur'] ?? []),
            obligationsInvestor: $strings($decoded['obligations_investor'] ?? []),
            conditionsPrecedent: $strings($decoded['conditions_precedent'] ?? []),
            milestones: $strings($decoded['milestones'] ?? []),
            terminationClauses: $text($decoded['termination_clauses'] ?? ''),
            disputeResolution: $text($decoded['dispute_resolution'] ?? ''),
            governingLaw: $text($decoded['governing_law'] ?? 'Droit OHADA et droit congolais des obligations'),
            deadline: $deadline,
            tokensUsed: max(0, $tokensUsed),
        );
    }
}
