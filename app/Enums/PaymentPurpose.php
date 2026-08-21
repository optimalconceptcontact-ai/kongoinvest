<?php

namespace App\Enums;

enum PaymentPurpose: string
{
    case Subscription = 'subscription';
    case Investment = 'investment'; // consumed by Phase 11's Escrow domain

    public function label(): string
    {
        return match ($this) {
            self::Subscription => 'Abonnement',
            self::Investment => 'Investissement',
        };
    }
}
