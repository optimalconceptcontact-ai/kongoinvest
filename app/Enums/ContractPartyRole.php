<?php

namespace App\Enums;

/**
 * Rôle contractuel d'une partie au sein de l'opération. La même
 * personne peut être simultanément actionnaire et créancière ;
 * les deux relations sont documentées séparément (prompt §5.C).
 */
enum ContractPartyRole: string
{
    case Founder = 'founder';
    case Company = 'company';
    case Investor = 'investor';
    case Mutual = 'mutual';

    public function label(): string
    {
        return match ($this) {
            self::Founder => 'Fondateur',
            self::Company => 'Société',
            self::Investor => 'Investisseur',
            self::Mutual => 'Parties conjointes',
        };
    }
}
