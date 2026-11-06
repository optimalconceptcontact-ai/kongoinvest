<?php

namespace App\Enums;

/**
 * Contrôles juridiques automatiques exécutés avant validation
 * finale (prompt §10 — Contrôles juridiques). Si le moteur ne
 * dispose pas d'une source fiable, le contrôle émet
 * CheckStatus::NeedsReview au lieu d'inventer une réponse
 * (prompt §16, règle 12).
 */
enum LegalCheckType: string
{
    case LegalFormCompatibility = 'legal_form_compatibility';
    case AuscgieCompliance = 'auscgie_compliance';
    case SecurityLawCompliance = 'security_law_compliance';
    case StatutesCoherence = 'statutes_coherence';
    case OrganCompetence = 'organ_competence';
    case TitleMechanismValidity = 'title_mechanism_validity';
    case CorporateDecisionsExistence = 'corporate_decisions_existence';
    case FormalitiesCompleteness = 'formalities_completeness';
    case RdcFinancialRegulation = 'rdc_financial_regulation';
    case ForeignInvestorRestrictions = 'foreign_investor_restrictions';
    case InstrumentConvertibility = 'instrument_convertibility';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::LegalFormCompatibility => 'Compatibilité forme sociale / mécanisme',
            self::AuscgieCompliance => 'Conformité AUSCGIE',
            self::SecurityLawCompliance => 'Conformité droit des sûretés',
            self::StatutesCoherence => 'Cohérence avec les statuts',
            self::OrganCompetence => 'Compétence des organes sociaux',
            self::TitleMechanismValidity => 'Validité du mécanisme de titres',
            self::CorporateDecisionsExistence => 'Décisions sociales requises',
            self::FormalitiesCompleteness => 'Formalités applicables',
            self::RdcFinancialRegulation => 'Réglementation financière et de crédit RDC',
            self::ForeignInvestorRestrictions => 'Restrictions investisseurs étrangers',
            self::InstrumentConvertibility => 'Convertibilité de l’instrument',
            self::Other => 'Autre contrôle juridique',
        };
    }
}
