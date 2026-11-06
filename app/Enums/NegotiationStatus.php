<?php

namespace App\Enums;

enum NegotiationStatus: string
{
    case Open = 'open';
    case Suspended = 'suspended';
    case Concluded = 'concluded';
    case Abandoned = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Ouverte',
            self::Suspended => 'Suspendue',
            self::Concluded => 'Conclue',
            self::Abandoned => 'Abandonnée',
        };
    }
}
