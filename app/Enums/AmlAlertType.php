<?php

namespace App\Enums;

enum AmlAlertType: string
{
    case PepMatch = 'pep_match';
    case PepAssociate = 'pep_associate';
    case SanctionsMatch = 'sanctions_match';
    case TargetedFinancialSanctions = 'targeted_financial_sanctions';
    case AdverseMedia = 'adverse_media';
    case HighRiskJurisdiction = 'high_risk_jurisdiction';
    case HighRiskSector = 'high_risk_sector';
    case SuspiciousDocument = 'suspicious_document';
    case IdentityMismatch = 'identity_mismatch';
    case UnidentifiedUbo = 'unidentified_ubo';
    case InadequateSourceOfFunds = 'inadequate_source_of_funds';
    case TransactionStructuring = 'transaction_structuring';
    case UnusualTransaction = 'unusual_transaction';
    case CashLimitExceeded = 'cash_limit_exceeded';

    public function label(): string
    {
        return match ($this) {
            self::PepMatch => 'Correspondance Personne Politiquement Exposée (PEP)',
            self::PepAssociate => 'Membre de la famille ou proche associé d\'une PEP (RCA)',
            self::SanctionsMatch => 'Correspondance avec une liste de sanctions internationales / nationales',
            self::TargetedFinancialSanctions => 'Mesure de gel des avoirs ou sanctions financières ciblées',
            self::AdverseMedia => 'Information défavorable dans les médias ou presse judiciaire',
            self::HighRiskJurisdiction => 'Implication d\'un pays ou juridiction à haut risque (GAFI / FATF)',
            self::HighRiskSector => 'Activité dans un secteur économique à haut risque',
            self::SuspiciousDocument => 'Document suspect ou altération détectée',
            self::IdentityMismatch => 'Incohérence entre les données déclarées et le document',
            self::UnidentifiedUbo => 'Bénéficiaire effectif non identifié ou chaîne opaque',
            self::InadequateSourceOfFunds => 'Origine des fonds insuffisamment justifiée',
            self::TransactionStructuring => 'Soupçon de fractionnement d\'opérations (structuring)',
            self::UnusualTransaction => 'Opération inhabituelle sans justification économique évidente',
            self::CashLimitExceeded => 'Tentative de dépassement de restriction sur les espèces (Art. 23 Loi 22/068)',
        };
    }
}
