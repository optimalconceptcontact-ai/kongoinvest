<?php

namespace App\Services\Escrow;

/**
 * Under the Coaching plan the app itself manages the investor/entrepreneur
 * relationship through to the investment's conclusion, and charges a
 * tiered accompaniment fee on the escrowed amount for that service — the
 * rate steps down as the investment grows. Thresholds are expressed per
 * currency since a raw amount_minor isn't comparable across USD/EUR/CDF.
 */
class AccompanimentFeeCalculator
{
    private const RATE_TIER_1 = 0.025;

    private const RATE_TIER_2 = 0.015;

    private const RATE_TIER_3 = 0.01;

    /** @var array<string, array{tier1Max: int, tier2Max: int}> amounts in minor units (×100) */
    private const THRESHOLDS = [
        'USD' => ['tier1Max' => 500_000, 'tier2Max' => 5_000_000],       // <=5,000 / 5,001-50,000 / >50,000
        'EUR' => ['tier1Max' => 500_000, 'tier2Max' => 5_000_000],       // <=5,000 / 5,001-50,000 / >50,000
        'CDF' => ['tier1Max' => 1_000_000_000, 'tier2Max' => 10_000_000_000], // <=10M / 10,000,001-100M / >100M
    ];

    public function rateFor(int $amountMinor, string $currency): float
    {
        $thresholds = self::THRESHOLDS[strtoupper($currency)] ?? self::THRESHOLDS['USD'];

        return match (true) {
            $amountMinor <= $thresholds['tier1Max'] => self::RATE_TIER_1,
            $amountMinor <= $thresholds['tier2Max'] => self::RATE_TIER_2,
            default => self::RATE_TIER_3,
        };
    }

    public function feeMinorFor(int $amountMinor, string $currency): int
    {
        return (int) round($amountMinor * $this->rateFor($amountMinor, $currency));
    }

    /** @return array{rate: float, feeMinor: int, totalMinor: int} */
    public function breakdown(int $amountMinor, string $currency): array
    {
        $rate = $this->rateFor($amountMinor, $currency);
        $feeMinor = (int) round($amountMinor * $rate);

        return ['rate' => $rate, 'feeMinor' => $feeMinor, 'totalMinor' => $amountMinor + $feeMinor];
    }
}
