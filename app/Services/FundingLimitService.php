<?php

namespace App\Services;

/**
 * Service de gestion des limites de financement par devise.
 *
 * Ce service centralise la logique des limites minimales et maximales
 * pour les montants de financement des projets (funding_target_minor).
 * Les limites sont définies par devise pour maintenir la cohérence et
 * la crédibilité de la plateforme.
 */
class FundingLimitService
{
    /**
     * Limites de financement par devise en unités mineures (centimes).
     *
     * Format : [devise => ['min' => montant_min, 'max' => montant_max]]
     *
     * - USD : 2 000 $ à 100 000 $ (200 000 à 10 000 000 centimes)
     * - CDF : 5 000 000 CDF à 250 000 000 CDF (500 000 000 à 25 000 000 000 centimes)
     * - EUR : 2 000 € à 100 000 € (200 000 à 10 000 000 centimes)
     */
    private const LIMITS = [
        'USD' => [
            'min' => 200_000,      // 2 000 $
            'max' => 10_000_000,   // 100 000 $
        ],
        'CDF' => [
            'min' => 500_000_000,      // 5 000 000 CDF
            'max' => 25_000_000_000,   // 250 000 000 CDF
        ],
        'EUR' => [
            'min' => 200_000,      // 2 000 €
            'max' => 10_000_000,   // 100 000 €
        ],
    ];

    /**
     * Obtient les limites min/max pour une devise donnée.
     *
     * @param  string  $currency  Code de devise ISO 4217 (ex: USD, CDF, EUR)
     * @return array{min: int, max: int}|null Tableau avec 'min' et 'max' en centimes, ou null si devise non supportée
     */
    public function getLimits(string $currency): ?array
    {
        return self::LIMITS[strtoupper($currency)] ?? null;
    }

    /**
     * Vérifie si un montant est dans les limites pour une devise donnée.
     *
     * @param  int  $amountMinor  Montant en unités mineures (centimes)
     * @param  string  $currency  Code de devise ISO 4217
     * @return bool True si le montant est dans les limites, false sinon
     */
    public function isWithinLimits(int $amountMinor, string $currency): bool
    {
        $limits = $this->getLimits($currency);

        if ($limits === null) {
            // Devise non supportée — retourne true par défaut pour ne pas bloquer
            return true;
        }

        return $amountMinor >= $limits['min'] && $amountMinor <= $limits['max'];
    }

    /**
     * Génère un message d'erreur de validation localisé en français.
     *
     * @param  string  $currency  Code de devise ISO 4217
     * @return string Message d'erreur formaté avec les limites de la devise
     */
    public function getValidationMessage(string $currency): string
    {
        $limits = $this->getLimits($currency);

        if ($limits === null) {
            return 'La devise spécifiée n\'est pas supportée.';
        }

        $min = $this->formatAmount($limits['min'], $currency);
        $max = $this->formatAmount($limits['max'], $currency);

        return "Le montant cible doit être entre {$min} et {$max}.";
    }

    /**
     * Retourne toutes les limites pour toutes les devises supportées.
     *
     * @return array<string, array{min: int, max: int}> Tableau associatif [devise => limites]
     */
    public function getAllLimits(): array
    {
        return self::LIMITS;
    }

    /**
     * Formate un montant en unités mineures vers une représentation lisible.
     *
     * @param  int  $amountMinor  Montant en centimes
     * @param  string  $currency  Code de devise
     * @return string Montant formaté avec le symbole de devise
     */
    public function formatAmount(int $amountMinor, string $currency): string
    {
        $majorAmount = $amountMinor / 100;

        // Formatage sans décimales pour les montants ronds
        $formatted = number_format($majorAmount, 0, ',', ' ');

        return "{$formatted} {$currency}";
    }

    /**
     * Vérifie si une devise est supportée par le système de limites.
     *
     * @param  string  $currency  Code de devise ISO 4217
     * @return bool True si la devise est supportée
     */
    public function isCurrencySupported(string $currency): bool
    {
        return isset(self::LIMITS[strtoupper($currency)]);
    }
}
