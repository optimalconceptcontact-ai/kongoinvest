<?php

namespace App\Enums;

enum TermCategory: string
{
    case Financial = 'financial';
    case Governance = 'governance';
    case Guarantee = 'guarantee';
    case Commitment = 'commitment';
    case Condition = 'condition';
    case Timeline = 'timeline';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Financial => 'Conditions financières',
            self::Governance => 'Gouvernance',
            self::Guarantee => 'Garanties',
            self::Commitment => 'Engagements',
            self::Condition => 'Conditions',
            self::Timeline => 'Échéances',
            self::Other => 'Autres',
        };
    }
}
