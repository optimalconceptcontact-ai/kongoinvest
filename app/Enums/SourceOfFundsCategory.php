<?php

namespace App\Enums;

enum SourceOfFundsCategory: string
{
    case EmploymentIncome = 'employment_income';
    case BusinessDividends = 'business_dividends';
    case AssetSale = 'asset_sale';
    case CompanyRevenue = 'company_revenue';
    case Inheritance = 'inheritance';
    case BankFinancing = 'bank_financing';
    case InvestmentReturn = 'investment_return';
    case FamilyGift = 'family_gift';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::EmploymentIncome => 'Salaires et revenus professionnels',
            self::BusinessDividends => 'Dividendes et distributions d\'entreprises',
            self::AssetSale => 'Vente d\'un actif immobilier ou mobilier',
            self::CompanyRevenue => 'Chiffre d\'affaires et trésorerie d\'exploitation',
            self::Inheritance => 'Succession / Héritage',
            self::BankFinancing => 'Crédit ou financement bancaire',
            self::InvestmentReturn => 'Plus-value ou retour sur investissement antérieur',
            self::FamilyGift => 'Donation familiale',
            self::Other => 'Autre origine justifiée',
        };
    }
}
