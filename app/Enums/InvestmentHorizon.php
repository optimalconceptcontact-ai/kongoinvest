<?php

namespace App\Enums;

enum InvestmentHorizon: string
{
    case ShortTerm = 'short_term'; // < 2 years
    case MediumTerm = 'medium_term'; // 2-5 years
    case LongTerm = 'long_term'; // 5+ years

    public function label(): string
    {
        return match ($this) {
            self::ShortTerm => 'Court terme (< 2 ans)',
            self::MediumTerm => 'Moyen terme (2-5 ans)',
            self::LongTerm => 'Long terme (5+ ans)',
        };
    }
}
