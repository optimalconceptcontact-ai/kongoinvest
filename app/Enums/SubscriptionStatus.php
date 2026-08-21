<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Trialing = 'trialing';
    case PastDue = 'past_due';
    case Canceled = 'canceled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Actif',
            self::Trialing => "Période d'essai",
            self::PastDue => 'Paiement en retard',
            self::Canceled => 'Annulé',
            self::Expired => 'Expiré',
        };
    }
}
