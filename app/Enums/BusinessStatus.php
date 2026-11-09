<?php

namespace App\Enums;

enum BusinessStatus: string
{
    case NotIncorporated = 'not_incorporated';
    case InFormation = 'in_formation';
    case Incorporated = 'incorporated';
    case Verified = 'verified';

    public function label(): string
    {
        return match ($this) {
            self::NotIncorporated => 'Projet non constitué en société',
            self::InFormation => 'Société en cours d\'immatriculation',
            self::Incorporated => 'Société constituée (RCCM obtenu)',
            self::Verified => 'Société vérifiée par la conformité (KYB validé)',
        };
    }
}
