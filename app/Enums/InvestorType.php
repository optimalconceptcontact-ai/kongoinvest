<?php

namespace App\Enums;

enum InvestorType: string
{
    case Individual = 'individual';
    case AngelInvestor = 'angel_investor';
    case VentureCapital = 'venture_capital';
    case PrivateEquity = 'private_equity';
    case FamilyOffice = 'family_office';
    case CorporateInvestor = 'corporate_investor';
    case DevelopmentFinanceInstitution = 'development_finance_institution';
    case Ngo = 'ngo';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Investisseur individuel',
            self::AngelInvestor => 'Business Angel',
            self::VentureCapital => 'Capital-risque (VC)',
            self::PrivateEquity => 'Private Equity',
            self::FamilyOffice => 'Family Office',
            self::CorporateInvestor => "Investisseur d'entreprise",
            self::DevelopmentFinanceInstitution => 'Institution de financement du développement',
            self::Ngo => 'ONG / Fondation',
            self::Other => 'Autre',
        };
    }
}
