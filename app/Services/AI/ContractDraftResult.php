<?php

namespace App\Services\Ai;

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
}
