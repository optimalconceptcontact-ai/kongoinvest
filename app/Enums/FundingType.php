<?php

namespace App\Enums;

enum FundingType: string
{
    case Equity = 'equity';
    case Debt = 'debt';
    case ConvertibleNote = 'convertible_note';
    case Grant = 'grant';
    case RevenueShare = 'revenue_share';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Equity => 'Prise de participation',
            self::Debt => 'Dette',
            self::ConvertibleNote => 'Obligation convertible',
            self::Grant => 'Subvention',
            self::RevenueShare => 'Partage de revenus',
            self::Other => 'Autre',
        };
    }
}
