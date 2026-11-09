<?php

namespace App\Enums;

enum UboControlNature: string
{
    case DirectEquity = 'direct_equity';
    case IndirectEquity = 'indirect_equity';
    case VotingRights = 'voting_rights';
    case LegalRepresentative = 'legal_representation';
    case ShareholdersAgreement = 'shareholders_agreement';
    case DeFactoControl = 'de_facto_control';
    case BeneficialEnjoyment = 'beneficial_enjoyment';

    public function label(): string
    {
        return match ($this) {
            self::DirectEquity => 'Détention directe du capital',
            self::IndirectEquity => 'Détention indirecte via société holding',
            self::VotingRights => 'Détention de droits de vote prépondérants',
            self::LegalRepresentative => 'Dirigeant principal / Représentant légal effectif',
            self::ShareholdersAgreement => 'Contrôle par pacte d\'actionnaires ou accord statutaire',
            self::DeFactoControl => 'Contrôle de fait / Pouvoir de nomination ou de révocation',
            self::BeneficialEnjoyment => 'Bénéficiaire économique ou fiduciaire',
        };
    }
}
