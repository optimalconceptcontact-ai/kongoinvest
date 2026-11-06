<?php

namespace App\Enums;

/**
 * Composante d'un instrument de financement. Chaque composante
 * d'une opération (capital / dette / convertible) est traitée,
 * contractualisée et formalisée séparément.
 */
enum FinancingComponentType: string
{
    case Capital = 'capital';
    case Debt = 'debt';
    case Convertible = 'convertible';

    public function label(): string
    {
        return match ($this) {
            self::Capital => 'Capital',
            self::Debt => 'Dette',
            self::Convertible => 'Instrument convertible',
        };
    }
}
