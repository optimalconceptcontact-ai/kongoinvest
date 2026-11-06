<?php

namespace App\Enums;

enum ContractDocumentType: string
{
    case LetterOfIntent = 'letter_of_intent';
    case TermSheet = 'term_sheet';
    case ConditionsSummary = 'conditions_summary';
    case CapTable = 'cap_table';
    case FinancingTable = 'financing_table';

    case CompanyDecision = 'company_decision';
    case SubscriptionDeed = 'subscription_deed';
    case TitleTransferDeed = 'title_transfer_deed';
    case UpdatedLegalStatus = 'updated_legal_status';
    case MemberAgreement = 'member_agreement';
    case GovernanceAnnex = 'governance_annex';

    case LoanAgreement = 'loan_agreement';
    case RepaymentSchedule = 'repayment_schedule';
    case InterestSchedule = 'interest_schedule';
    case SecurityDocument = 'security_document';
    case CovenantAnnex = 'covenant_annex';
    case DisbursementAnnex = 'disbursement_annex';

    case ConsolidatedTermSheet = 'consolidated_term_sheet';
    case FrameworkAgreement = 'framework_agreement';
    case RealizationSchedule = 'realization_schedule';
    case ConditionsPrecedentList = 'conditions_precedent_list';
    case ClosingMinutes = 'closing_minutes';

    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::LetterOfIntent => 'Lettre d’intention (LOI)',
            self::TermSheet => 'Term Sheet',
            self::ConditionsSummary => 'Synthèse des conditions négociées',
            self::CapTable => 'Tableau de capitalisation',
            self::FinancingTable => 'Tableau de financement',
            self::CompanyDecision => 'Décision sociale',
            self::SubscriptionDeed => 'Acte de souscription',
            self::TitleTransferDeed => 'Acte de cession de titres',
            self::UpdatedLegalStatus => 'Statuts mis à jour',
            self::MemberAgreement => 'Pacte d’associés / d’actionnaires',
            self::GovernanceAnnex => 'Annexe de gouvernance',
            self::LoanAgreement => 'Contrat de prêt',
            self::RepaymentSchedule => 'Échéancier de remboursement',
            self::InterestSchedule => 'Tableau de calcul des intérêts',
            self::SecurityDocument => 'Acte de garantie',
            self::CovenantAnnex => 'Annexe des engagements financiers',
            self::DisbursementAnnex => 'Annexe des conditions de décaissement',
            self::ConsolidatedTermSheet => 'Term Sheet consolidé',
            self::FrameworkAgreement => 'Convention-cadre / contrat d’investissement',
            self::RealizationSchedule => 'Calendrier de réalisation',
            self::ConditionsPrecedentList => 'Liste des conditions suspensives',
            self::ClosingMinutes => 'Procès-verbal de clôture',
            self::Other => 'Autre document',
        };
    }
}
