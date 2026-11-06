<?php

namespace App\Enums;

/**
 * Nature de la valeur portée par une condition négociée. Sert à
 * rendre la synthèse lisible et à orienter les contrôles de
 * cohérence (montants, pourcentages, échéances…).
 */
enum TermKind: string
{
    case Amount = 'amount';
    case Percentage = 'percentage';
    case Valuation = 'valuation';
    case InterestRate = 'interest_rate';
    case Duration = 'duration';
    case Guarantees = 'guarantees';
    case GovernanceRight = 'governance_right';
    case ClausePreference = 'clause_preference';
    case Commitment = 'commitment';
    case SuspensiveCondition = 'suspensive_condition';
    case Deadline = 'deadline';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Amount => 'Montant',
            self::Percentage => 'Pourcentage',
            self::Valuation => 'Valorisation',
            self::InterestRate => 'Taux d’intérêt',
            self::Duration => 'Durée',
            self::Guarantees => 'Garanties',
            self::GovernanceRight => 'Droit de gouvernance',
            self::ClausePreference => 'Préférence de clause',
            self::Commitment => 'Engagement',
            self::SuspensiveCondition => 'Condition suspensive',
            self::Deadline => 'Échéance',
            self::Other => 'Autre',
        };
    }
}
