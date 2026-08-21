<?php

namespace App\Enums;

enum ProjectVisibility: string
{
    case Public = 'public';
    case MatchedInvestors = 'matched_investors';
    case ConnectionOnly = 'connection_only';
    case Private = 'private';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Public',
            self::MatchedInvestors => 'Investisseurs matchés uniquement',
            self::ConnectionOnly => 'Connexions uniquement',
            self::Private => 'Privé',
        };
    }
}
