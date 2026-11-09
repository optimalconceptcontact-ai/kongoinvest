<?php

namespace App\Enums;

enum DocumentType: string
{
    case Passport = 'passport';
    case DrcVoterCard = 'drc_voter_card';
    case NationalId = 'national_id';
    case DrivingLicense = 'driving_license';
    case ResidencePermit = 'residence_permit';
    case OtherOfficialId = 'other_official_id';

    public function label(): string
    {
        return match ($this) {
            self::Passport => 'Passeport biométrique',
            self::DrcVoterCard => "Carte d'électeur RDC (CENI)",
            self::NationalId => "Carte nationale d'identité (CNI / ONIP)",
            self::DrivingLicense => 'Permis de conduire',
            self::ResidencePermit => 'Permis ou carte de séjour',
            self::OtherOfficialId => 'Autre pièce officielle d\'identité',
        };
    }

    public function requiresBackSide(): bool
    {
        return in_array($this, [self::DrcVoterCard, self::NationalId, self::DrivingLicense, self::ResidencePermit], true);
    }
}
