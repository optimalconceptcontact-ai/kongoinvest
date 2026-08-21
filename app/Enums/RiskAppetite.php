<?php

namespace App\Enums;

enum RiskAppetite: string
{
    case Conservative = 'conservative';
    case Moderate = 'moderate';
    case Aggressive = 'aggressive';

    public function label(): string
    {
        return match ($this) {
            self::Conservative => 'Prudent',
            self::Moderate => 'Modéré',
            self::Aggressive => 'Agressif',
        };
    }
}
