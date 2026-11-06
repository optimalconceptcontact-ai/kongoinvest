<?php

namespace App\Enums;

enum ClauseCategory: string
{
    case Capital = 'capital';
    case Debt = 'debt';
    case MixedCoordination = 'mixed_coordination';
    case Governance = 'governance';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::Capital => 'Clauses relatives au capital',
            self::Debt => 'Clauses relatives à la dette',
            self::MixedCoordination => 'Coordination capital / dette',
            self::Governance => 'Gouvernance',
            self::General => 'Clauses générales',
        };
    }
}
