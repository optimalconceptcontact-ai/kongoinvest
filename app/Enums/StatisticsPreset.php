<?php

namespace App\Enums;

enum StatisticsPreset: string
{
    case Week = 'week';
    case Month = 'month';
    case Year = 'year';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Week => 'Semaine',
            self::Month => 'Mois',
            self::Year => 'Année',
            self::Custom => 'Personnalisé',
        };
    }
}
