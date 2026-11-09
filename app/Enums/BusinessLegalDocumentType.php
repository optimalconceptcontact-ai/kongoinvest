<?php

namespace App\Enums;

enum BusinessLegalDocumentType: string
{
    case Rccm = 'rccm';
    case Statutes = 'statutes';
    case IncorporationDeed = 'incorporation_deed';
    case Nif = 'nif';
    case NationalIdentification = 'national_identification';
    case NominationMinutes = 'nomination_minutes';
    case BoardResolution = 'board_resolution';
    case ShareholdersList = 'shareholders_list';
    case UboChart = 'ubo_chart';
    case AddressProof = 'address_proof';
    case SectorialAuthorization = 'sectorial_authorization';
    case FinancialStatements = 'financial_statements';
    case BankReferenceLetter = 'bank_reference_letter';

    public function label(): string
    {
        return match ($this) {
            self::Rccm => 'Extrait RCCM immatriculation',
            self::Statutes => 'Statuts notariés en vigueur',
            self::IncorporationDeed => 'Acte de constitution',
            self::Nif => 'Attestation de Numéro d\'Identification Fiscale (NIF)',
            self::NationalIdentification => 'Numéro d\'Identification Nationale (Id. Nat.)',
            self::NominationMinutes => 'Procès-verbal de nomination des dirigeants',
            self::BoardResolution => 'Délibération sociale autorisant l\'opération',
            self::ShareholdersList => 'Liste certifiée des associés ou actionnaires',
            self::UboChart => 'Organigramme des bénéficiaires effectifs certifié',
            self::AddressProof => 'Justificatif d\'adresse du siège social',
            self::SectorialAuthorization => 'Agrément / Autorisation sectorielle',
            self::FinancialStatements => 'États financiers certifiés ou bilans',
            self::BankReferenceLetter => 'Attestation de domiciliation bancaire',
        };
    }
}
