<?php

namespace App\Enums;

/**
 * Nature de l'opération de financement. Distingue clairement
 * le capital de la dette — les composantes d'une opération mixte
 * sont modélisées séparément (prompt §16, règle 5).
 */
enum FinancingCategory: string
{
    case Equity = 'equity';
    case Debt = 'debt';
    case Convertible = 'convertible';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::Equity => 'Investissement en capital',
            self::Debt => 'Financement par dette',
            self::Convertible => 'Financement convertible',
            self::Mixed => 'Financement mixte',
        };
    }
}
