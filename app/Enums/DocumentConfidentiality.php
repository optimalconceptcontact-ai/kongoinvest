<?php

namespace App\Enums;

enum DocumentConfidentiality: string
{
    case Shared = 'shared';
    case FounderOnly = 'founder_only';
    case InvestorOnly = 'investor_only';

    public function label(): string
    {
        return match ($this) {
            self::Shared => 'Partagé',
            self::FounderOnly => 'Réservé au fondateur',
            self::InvestorOnly => 'Réservé à l’investisseur',
        };
    }

    /**
     * Le document est visible pour l'utilisateur donné. Les rôles
     * sont résolus côté DealRoom (entrepreneur_user_id /
     * investor_user_id) ; l'appelant passe le rôle effectif.
     */
    public function isVisibleTo(bool $isFounder): bool
    {
        return match ($this) {
            self::Shared => true,
            self::FounderOnly => $isFounder,
            self::InvestorOnly => ! $isFounder,
        };
    }
}
