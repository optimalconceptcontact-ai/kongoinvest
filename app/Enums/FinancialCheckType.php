<?php

namespace App\Enums;

/**
 * Contrôles de cohérence financière déterministes — jamais
 * délégués à un modèle d'IA (prompt §13 : les calculs financiers
 * et les contrôles de cohérence doivent être déterministes et
 * testables).
 */
enum FinancialCheckType: string
{
    case AmountConsistency = 'amount_consistency';
    case PercentageConsistency = 'percentage_consistency';
    case ValuationConsistency = 'valuation_consistency';
    case DilutionCalculation = 'dilution_calculation';
    case InterestCalculation = 'interest_calculation';
    case ScheduleConsistency = 'schedule_consistency';
    case RepaymentCapacity = 'repayment_capacity';
    case DscrRatio = 'dscr_ratio';
    case CollateralCoverage = 'collateral_coverage';
    case DoubleCounting = 'double_counting';

    public function label(): string
    {
        return match ($this) {
            self::AmountConsistency => 'Cohérence des montants',
            self::PercentageConsistency => 'Cohérence des pourcentages',
            self::ValuationConsistency => 'Cohérence des valorisations',
            self::DilutionCalculation => 'Calcul de dilution',
            self::InterestCalculation => 'Calcul des intérêts',
            self::ScheduleConsistency => 'Cohérence de l’échéancier',
            self::RepaymentCapacity => 'Capacité de remboursement',
            self::DscrRatio => 'Ratio DSCR',
            self::CollateralCoverage => 'Couverture des garanties',
            self::DoubleCounting => 'Double comptage',
        };
    }
}
