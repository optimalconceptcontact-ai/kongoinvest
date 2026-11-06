<?php

namespace App\Enums;

/**
 * Mécanisme juridique d'entrée de l'investisseur au capital.
 * Une augmentation de capital et une cession de titres n'ont ni
 * les mêmes décisions sociales, ni les mêmes formalités : le
 * mécanisme est explicitement identifié (prompt §3.B.1, §6).
 */
enum EquityMechanism: string
{
    case CapitalIncrease = 'capital_increase';
    case TitleTransfer = 'title_transfer';

    public function label(): string
    {
        return match ($this) {
            self::CapitalIncrease => 'Augmentation de capital',
            self::TitleTransfer => 'Cession de titres',
        };
    }
}
