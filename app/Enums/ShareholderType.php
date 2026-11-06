<?php

namespace App\Enums;

enum ShareholderType: string
{
    case Individual = 'individual';
    case LegalEntity = 'legal_entity';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Personne physique',
            self::LegalEntity => 'Personne morale',
        };
    }
}
