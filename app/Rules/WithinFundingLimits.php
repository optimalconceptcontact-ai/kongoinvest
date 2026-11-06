<?php

namespace App\Rules;

use App\Services\FundingLimitService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Règle de validation pour les limites de financement par devise.
 *
 * Vérifie qu'un montant de financement (en unités mineures/centimes)
 * respecte les limites minimales et maximales définies pour la devise
 * associée. Utilise FundingLimitService pour la logique métier.
 */
class WithinFundingLimits implements ValidationRule
{
    private FundingLimitService $fundingLimitService;

    /**
     * Crée une nouvelle instance de la règle.
     *
     * @param  string  $currency  Code de devise ISO 4217 (USD, CDF, EUR)
     */
    public function __construct(private string $currency)
    {
        $this->fundingLimitService = app(FundingLimitService::class);
    }

    /**
     * Exécute la validation.
     *
     * @param  string  $attribute  Nom de l'attribut validé
     * @param  mixed  $value  Valeur à valider (montant en centimes)
     * @param  Closure(string): PotentiallyTranslatedString  $fail  Callback d'échec
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Si la valeur est null ou vide, on laisse passer (la règle 'nullable' gère ça)
        if ($value === null || $value === '') {
            return;
        }

        // Conversion en entier si nécessaire
        $amountMinor = is_numeric($value) ? (int) $value : 0;

        // Vérification des limites
        if (! $this->fundingLimitService->isWithinLimits($amountMinor, $this->currency)) {
            $fail($this->fundingLimitService->getValidationMessage($this->currency));
        }
    }
}
