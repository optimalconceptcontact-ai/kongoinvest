<?php

namespace App\Enums;

enum MatchStatus: string
{
    case Pending = 'pending'; // computed, not yet seen by the investor
    case Viewed = 'viewed';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Nouveau',
            self::Viewed => 'Consulté',
            self::Dismissed => 'Ignoré',
        };
    }
}
