<?php

namespace App\Enums;

enum AmlAlertStatus: string
{
    case Open = 'open';
    case UnderInvestigation = 'under_investigation';
    case FalsePositive = 'false_positive';
    case ConfirmedMatch = 'confirmed_match';
    case Escalated = 'escalated';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Ouverte (à examiner)',
            self::UnderInvestigation => 'En cours d\'investigation',
            self::FalsePositive => 'Faux positif avéré',
            self::ConfirmedMatch => 'Correspondance confirmée',
            self::Escalated => 'Escaladée au responsable de conformité',
            self::Resolved => 'Résolue / Clôturée',
        };
    }
}
