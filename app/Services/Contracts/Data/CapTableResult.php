<?php

namespace App\Services\Contracts\Data;

/**
 * Résultat déterministe du calcul de table de capitalisation
 * après opération (CapTableCalculationService). Isolation de la
 * dette : seuls les instruments donnant accès au capital
 * (augmentation de capital, cession de titres) y participent —
 * la dette n'y figure jamais (prompt §16, règle 5).
 */
final readonly class CapTableResult
{
    /**
     * @param  array<int, array<string, mixed>>  $entries
     * @param  list<string>  $warnings
     */
    public function __construct(
        public readonly string $mechanism, // App\Enums\EquityMechanism value
        public readonly string $currency,
        public readonly int $preMoneyMinor,
        public readonly int $postMoneyMinor, // valorisation réellement utilisée pour le calcul
        public readonly int $capitalAmountMinor, // argent donnant accès au capital — jamais la dette
        public readonly int $shareCountBefore,
        public readonly int $newShares, // 0 pour une cession
        public readonly int $shareCountAfter,
        public readonly ?int $pricePerShareMinor,
        public readonly bool $pricePerShareExact,
        public readonly int $investorShareBps, // participation projetée exacte
        public readonly int $founderShareBps,
        public readonly ?int $targetInvestorShareBps, // participation négociée, si fixée
        public readonly bool $targetAchievable,
        public readonly bool $verifiedData,
        public readonly array $entries,
        public readonly array $warnings,
    ) {}

    /**
     * Payload persistable dans investment_contract_workspaces.
     * post_operation_cap_table (affichage + contrôles de
     * cohérence) — structuration stable, pas de DateTime objet.
     */
    public function toSnapshot(): array
    {
        return [
            'mechanism' => $this->mechanism,
            'currency' => $this->currency,
            'pre_money_minor' => $this->preMoneyMinor,
            'post_money_minor' => $this->postMoneyMinor,
            'capital_amount_minor' => $this->capitalAmountMinor,
            'share_count_before' => $this->shareCountBefore,
            'new_shares' => $this->newShares,
            'share_count_after' => $this->shareCountAfter,
            'price_per_share_minor' => $this->pricePerShareMinor,
            'price_per_share_exact' => $this->pricePerShareExact,
            'investor_share_bps' => $this->investorShareBps,
            'founder_share_bps' => $this->founderShareBps,
            'target_investor_share_bps' => $this->targetInvestorShareBps,
            'target_achievable' => $this->targetAchievable,
            'verified_data' => $this->verifiedData,
            'entries' => $this->entries,
            'warnings' => $this->warnings,
        ];
    }
}
