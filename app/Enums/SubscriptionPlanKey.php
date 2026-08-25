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
            // Displayed as "Coaching" — the app itself now manages the
            // investor/entrepreneur relationship through to closing under
            // this plan. The enum case/value stay 'professional' so
            // existing DB rows, tests, and role-scope checks are untouched.
            self::Professional => 'Coaching',
        };
    }
}
