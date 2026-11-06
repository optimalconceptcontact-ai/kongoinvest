<?php

namespace App\Enums;

enum ContractConditionType: string
{
    case Suspensive = 'suspensive';
    case Resolutive = 'resolutive';
    case Obligation = 'obligation';
    case Covenant = 'covenant';

    public function label(): string
    {
        return match ($this) {
            self::Suspensive => 'Condition suspensive',
            self::Resolutive => 'Condition résolutive',
            self::Obligation => 'Obligation contractuelle',
            self::Covenant => 'Engagement financier (covenant)',
        };
    }
}
