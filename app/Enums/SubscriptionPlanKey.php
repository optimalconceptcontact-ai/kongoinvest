<?php

namespace App\Enums;

enum SubscriptionPlanKey: string
{
    case Free = 'free';
    case Entrepreneur = 'entrepreneur';
    case Investor = 'investor';
    case Professional = 'professional';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Entrepreneur => 'Entrepreneur',
            self::Investor => 'Investor',
            self::Professional => 'Professional',
        };
    }
}
