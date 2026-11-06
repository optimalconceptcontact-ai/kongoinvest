import type { FormDataConvertible } from '@inertiajs/core';
import {
    Head,
    Link as InertiaLink,
    router,
    usePage,
} from '@inertiajs/react';
import {
    RiAddLine,
    RiAlertLine,
    RiCalendarLine,
    RiCheckboxCircleLine,
    RiDeleteBinLine,
    RiFileCopy2Line,
    RiFileTextLine,
    RiLockLine,
    RiRefreshLine,
    RiShieldCheckLine,
    RiSparklingLine,
} from '@remixicon/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Link } from '@/components/ui/Link';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/Textarea';

// ---------------------------------------------------------------------------
// Types — miroir Strict du payload ContractWorkspaceData (camelCase, tout
// état déjà calculé côté serveur : la page n'interprète rien, elle affiche).
// ---------------------------------------------------------------------------

type WorkspaceStatusValue =
    | 'draft'
    | 'setup'
    | 'negotiation'
    | 'clause_selection'
    | 'documentation'
    | 'review'
    | 'approval'
    | 'signature'
    | 'closing'
    | 'closed'
    | 'cancelled';

type TermStatusValue =
    | 'initial_proposal'
    | 'counter_proposal'
    | 'explicitly_accepted'
    | 'explicitly_refused'
    | 'under_discussion'
    | 'to_clarify'
    | 'contradictory'
    | 'to_confirm_by_document'
    | 'formalized';

type ClauseStatusValue =
    | 'not_proposed'
    | 'proposed_by_ai'
    | 'proposed_by_founder'
    | 'proposed_by_investor'
    | 'in_negotiation'
    | 'accepted_by_both'
    | 'refused'
    | 'to_revise'
    | 'pending_legal_validation';

type ClauseDecisionValue =
    | 'accept'
    | 'refuse'
    | 'counter_proposal'
    | 'request_revision';

type ComponentTypeValue = 'capital' | 'debt' | 'convertible';

type EquityMechanismValue = 'capital_increase' | 'title_transfer';

type DocumentStatusValue =
    | 'draft'
    | 'in_review'
    | 'approved'
    | 'pending_signature'
    | 'signed'
    | 'archived'
    | 'cancelled';

type DocumentTypeValue =
    | 'letter_of_intent'
    | 'term_sheet'
    | 'conditions_summary'
    | 'cap_table'
    | 'financing_table'
    | 'company_decision'
    | 'subscription_deed'
    | 'title_transfer_deed'
    | 'updated_legal_status'
    | 'member_agreement'
    | 'governance_annex'
    | 'loan_agreement'
    | 'repayment_schedule'
    | 'interest_schedule'
    | 'security_document'
    | 'covenant_annex'
    | 'disbursement_annex'
    | 'consolidated_term_sheet'
    | 'framework_agreement'
    | 'realization_schedule'
    | 'conditions_precedent_list'
    | 'closing_minutes'
    | 'other';

type ConfidentialityValue = 'shared' | 'founder_only' | 'investor_only';

type ConditionTypeValue = 'suspensive' | 'resolutive' | 'obligation' | 'covenant';

type ConditionStatusValue = 'pending' | 'fulfilled' | 'not_fulfilled' | 'waived';

type PartyRoleValue = 'founder' | 'company' | 'investor' | 'mutual';

type SignatureMethodValue = 'electronic_provider' | 'external';

type SignatureStatusValue = 'requested' | 'completed' | 'refused' | 'void';

type CheckSeverityValue = 'critical' | 'high' | 'medium' | 'low';

type CheckStatusValue = 'passed' | 'warning' | 'failed' | 'needs_review';

type FinancialCheckTypeValue =
    | 'amount_consistency'
    | 'percentage_consistency'
    | 'valuation_consistency'
    | 'dilution_calculation'
    | 'interest_calculation'
    | 'schedule_consistency'
    | 'repayment_capacity'
    | 'dscr_ratio'
    | 'collateral_coverage'
    | 'double_counting';

// Miroir de App\Enums\LegalCheckType — 12 types strictement
// identiques à l'enum côté serveur.
type LegalCheckTypeValue =
    | 'legal_form_compatibility'
    | 'auscgie_compliance'
    | 'security_law_compliance'
    | 'statutes_coherence'
    | 'organ_competence'
    | 'title_mechanism_validity'
    | 'corporate_decisions_existence'
    | 'formalities_completeness'
    | 'rdc_financial_regulation'
    | 'foreign_investor_restrictions'
    | 'instrument_convertibility'
    | 'other';

type CheckDraft = {
    id: number;
    batch: string;
    type: FinancialCheckTypeValue | LegalCheckTypeValue;
    severity: CheckSeverityValue;
    status: CheckStatusValue;
    title: string;
    message: string;
    resolvedAt: string | null;
};

type WorkspaceData = {
    id: number;
    dealRoomId: number;
    projectTitle: string;
    projectSlug: string;
    entrepreneurName: string;
    investorName: string;
    status: WorkspaceStatusValue;
    currency: string;
    financingCategory: string | null;
    legalFormLabel: string | null;
    viewerRole: 'entrepreneur' | 'investor' | 'observer';
    entrepreneurConsentAt: string | null;
    investorConsentAt: string | null;
    bothConsented: boolean;
    preMoneyMinor: number | null;
    postMoneyMinor: number | null;
    totalInvestmentMinor: number | null;
    founderShareBps: number | null;
    investorShareBps: number | null;
    financingComponents: {
        id: number;
        type: ComponentTypeValue;
        amountMinor: number | null;
        currency: string;
        isPrimary: boolean;
    }[];
    terms: {
        id: number;
        subject: string;
        label: string;
        category: string;
        kind: string;
        instrument: string | null;
        value: Record<string, unknown> | null;
        displayValue: string | null;
        status: TermStatusValue;
        conflictWithTermId: number | null;
        sourceContext: Record<string, unknown> | null;
        notes: string | null;
    }[];
    clauses: {
        id: number;
        title: string;
        category: string;
        origin: string;
        status: ClauseStatusValue;
        instrumentScope: string | null;
        variant: string | null;
        body: string | null;
        rationale: string | null;
        founderDecision: ClauseDecisionValue | null;
        investorDecision: ClauseDecisionValue | null;
    }[];
    snapshots: {
        id: number;
        version: number;
        status: 'pending' | 'processing' | 'completed' | 'failed';
        tokensUsed: number | null;
        messageCount: number | null;
        synthesis: {
            summary?: string;
            counts?: {
                terms_kept?: number;
                terms_with_conflicts?: number;
                clause_recommendations_kept?: number;
                clause_recommendations_ignored?: number;
            };
            ignored_recommendations?: string[];
        } | null;
        completedAt: string | null;
        failureReason: string | null;
    }[];
    postOperationCapTable: {
        mechanism: string | null;
        currency: string | null;
        preMoneyMinor: number | null;
        postMoneyMinor: number | null;
        capitalAmountMinor: number | null;
        shareCountBefore: number | null;
        newShares: number | null;
        shareCountAfter: number | null;
        pricePerShareMinor: number | null;
        pricePerShareExact: boolean | null;
        investorShareBps: number | null;
        founderShareBps: number | null;
        targetInvestorShareBps: number | null;
        targetAchievable: boolean | null;
        verifiedData: boolean | null;
        entries: {
            holderName: string | null;
            holderType: string | null;
            isFounder: boolean;
            isInvestor: boolean;
            sharesBefore: number | null;
            sharesAfter: number | null;
            percentageBeforeBps: number | null;
            percentageAfterBps: number | null;
        }[];
        warnings: string[];
    } | null;
    documents: {
        id: number;
        type: DocumentTypeValue;
        title: string;
        status: DocumentStatusValue;
        confidentiality: ConfidentialityValue;
        currentVersion: number | null;
        founderApprovedAt: string | null;
        investorApprovedAt: string | null;
        legalValidatedAt: string | null;
        signedDepositedAt: string | null;
    }[];
    conditions: {
        id: number;
        type: ConditionTypeValue;
        responsibleParty: PartyRoleValue;
        description: string;
        dueDate: string | null;
        status: ConditionStatusValue;
        documentId: number | null;
        fulfilledAt: string | null;
    }[];
    signatures: {
        id: number;
        documentId: number;
        signerRole: PartyRoleValue;
        method: SignatureMethodValue;
        status: SignatureStatusValue;
        signedAt: string | null;
        hasEvidence: boolean;
    }[];
    financialChecks: CheckDraft[];
    hasBlockingFinancialCheck: boolean;
    legalChecks: CheckDraft[];
    hasBlockingLegalCheck: boolean;
    clauseLibrary: {
        id: number;
        title: string;
        category: string;
        variants: string[];
    }[];
    legalReviewedAt: string | null;
    lastActivityAt: string | null;
};

type SharedProps = {
    auth: {
        user: { id: number; name: string; email: string } | null;
        roles: string[];
        permissions: string[];
    };
    errors: Record<string, string>;
    flash?: { success: string | null; error: string | null };
};

// ---------------------------------------------------------------------------
// Libellés — miroir des enums PHP. Une valeur inconnue reste brute (mono) :
// jamais inventée côté client.
// ---------------------------------------------------------------------------

const workspaceStatusLabel: Partial<Record<string, string>> = {
    draft: 'Ébauche',
    setup: 'Configuration',
    negotiation: 'Négociation',
    clause_selection: 'Sélection des clauses',
    documentation: 'Documentation',
    review: 'Vérifications',
    approval: 'Validation',
    signature: 'Signature',
    closing: 'Clôture',
    closed: 'Clôturée',
    cancelled: 'Annulée',
};

const termStatusLabel: Partial<Record<string, string>> = {
    initial_proposal: 'Proposition initiale',
    counter_proposal: 'Contre-proposition',
    explicitly_accepted: 'Acceptée explicitement',
    explicitly_refused: 'Refusée explicitement',
    under_discussion: 'En discussion',
    to_clarify: 'À clarifier',
    contradictory: 'Contradictoire',
    to_confirm_by_document: 'À confirmer par un document',
    formalized: 'Engagement formalisé',
};

const termCategoryLabel: Partial<Record<string, string>> = {
    financial: 'Financier',
    governance: 'Gouvernance',
    guarantee: 'Garantie',
    commitment: 'Engagement',
    condition: 'Condition',
    timeline: 'Calendrier',
    other: 'Autre',
};

const termKindLabel: Partial<Record<string, string>> = {
    amount: 'Montant',
    percentage: 'Pourcentage',
    valuation: 'Valorisation',
    interest_rate: "Taux d'intérêt",
    duration: 'Durée',
    guarantees: 'Garanties',
    governance_right: 'Droit de gouvernance',
    clause_preference: 'Préférence de clause',
    commitment: 'Engagement',
    suspensive_condition: 'Condition suspensive',
    deadline: 'Échéance',
    other: 'Autre',
};

const clauseStatusLabel: Partial<Record<string, string>> = {
    not_proposed: 'Non proposée',
    proposed_by_ai: 'Proposée par l\'IA',
    proposed_by_founder: 'Proposée par le fondateur',
    proposed_by_investor: 'Proposée par l\'investisseur',
    in_negotiation: 'En négociation',
    accepted_by_both: 'Acceptée par les deux parties',
    refused: 'Refusée',
    to_revise: 'À réviser',
    pending_legal_validation: 'En attente de validation juridique',
};

const clauseOriginLabel: Partial<Record<string, string>> = {
    ai: 'IA',
    founder: 'Fondateur',
    investor: 'Investisseur',
    custom: 'Personnalisée',
};

const clauseCategoryLabel: Partial<Record<string, string>> = {
    capital: 'Capital',
    debt: 'Dette',
    mixed_coordination: 'Coordination capital / dette',
    governance: 'Gouvernance',
    general: 'Générales',
};

const clauseDecisionLabel: Partial<Record<string, string>> = {
    accept: 'Acceptée',
    refuse: 'Refusée',
    counter_proposal: 'Contre-proposition',
    request_revision: 'Révision demandée',
};

const componentTypeLabel: Partial<Record<string, string>> = {
    capital: 'Capital',
    debt: 'Dette',
    convertible: 'Instrument convertible',
};

const equityMechanismLabel: Partial<Record<string, string>> = {
    capital_increase: 'Augmentation de capital',
    title_transfer: 'Cession de titres',
};

const financingCategoryLabel: Partial<Record<string, string>> = {
    equity: 'Investissement en capital',
    debt: 'Financement par dette',
    convertible: 'Financement convertible',
    mixed: 'Financement mixte',
};

const snapshotStatusLabel: Partial<Record<string, string>> = {
    pending: 'En attente',
    processing: 'En traitement',
    completed: 'Terminée',
    failed: 'Échouée',
};

const documentStatusLabel: Partial<Record<string, string>> = {
    draft: 'Projet',
    in_review: 'En relecture',
    approved: 'Approuvé',
    pending_signature: 'En attente de signature',
    signed: 'Signé',
    archived: 'Archivé',
    cancelled: 'Annulé',
};

const documentTypeLabel: Partial<Record<string, string>> = {
    letter_of_intent: "Lettre d'intention (LOI)",
    term_sheet: 'Term Sheet',
    conditions_summary: 'Synthèse des conditions négociées',
    cap_table: 'Tableau de capitalisation',
    financing_table: 'Tableau de financement',
    company_decision: 'Décision sociale',
    subscription_deed: 'Acte de souscription',
    title_transfer_deed: 'Acte de cession de titres',
    updated_legal_status: 'Statuts mis à jour',
    member_agreement: "Pacte d'associés / d'actionnaires",
    governance_annex: 'Annexe de gouvernance',
    loan_agreement: 'Contrat de prêt',
    repayment_schedule: 'Échéancier de remboursement',
    interest_schedule: 'Tableau de calcul des intérêts',
    security_document: 'Acte de garantie',
    covenant_annex: 'Annexe des engagements financiers',
    disbursement_annex: 'Annexe des conditions de décaissement',
    consolidated_term_sheet: 'Term Sheet consolidé',
    framework_agreement: "Convention-cadre / contrat d'investissement",
    realization_schedule: 'Calendrier de réalisation',
    conditions_precedent_list: 'Liste des conditions suspensives',
    closing_minutes: 'Procès-verbal de clôture',
    other: 'Autre document',
};

const confidentialityLabel: Partial<Record<string, string>> = {
    shared: 'Partagé',
    founder_only: 'Visible du fondateur seul',
    investor_only: "Visible de l'investisseur seul",
};

const conditionTypeLabel: Partial<Record<string, string>> = {
    suspensive: 'Condition suspensive',
    resolutive: 'Condition résolutive',
    obligation: 'Obligation contractuelle',
    covenant: 'Engagement financier (covenant)',
};

const conditionStatusLabel: Partial<Record<string, string>> = {
    pending: 'En attente',
    fulfilled: 'Conduite à terme',
    not_fulfilled: 'Non réalisée',
    waived: 'Levée',
};

const partyRoleLabel: Partial<Record<string, string>> = {
    founder: 'Fondateur',
    company: 'Société',
    investor: 'Investisseur',
    mutual: 'Commune aux deux parties',
};

const signatureMethodLabel: Partial<Record<string, string>> = {
    electronic_provider: 'Signature électronique (prestataire)',
    external: 'Signature externe (dépôt des exemplaires signés)',
};

const signatureStatusLabel: Partial<Record<string, string>> = {
    requested: 'Demandée',
    completed: 'Signée',
    refused: 'Refusée',
    void: 'Annulée',
};

const checkStatusLabel: Partial<Record<string, string>> = {
    passed: 'Conforme',
    warning: 'Avertissement',
    failed: 'Échec',
    needs_review: 'Vérification humaine requise',
};

const checkSeverityLabel: Partial<Record<string, string>> = {
    critical: 'Critique',
    high: 'Élevée',
    medium: 'Moyenne',
    low: 'Faible',
};

const financialCheckTypeLabel: Partial<Record<string, string>> = {
    amount_consistency: 'Cohérence des montants',
    percentage_consistency: 'Cohérence des pourcentages',
    valuation_consistency: 'Cohérence des valorisations',
    dilution_calculation: 'Calcul de dilution',
    interest_calculation: 'Calcul des intérêts',
    schedule_consistency: "Cohérence de l'échéancier",
    repayment_capacity: 'Capacité de remboursement',
    dscr_ratio: 'Ratio DSCR',
    collateral_coverage: 'Couverture des garanties',
    double_counting: 'Double comptage',
};

// Miroir de App\Enums\LegalCheckType::label() — libellés FR
// strictement identiques côté serveur.
const legalCheckTypeLabel: Partial<Record<string, string>> = {
    legal_form_compatibility: 'Compatibilité forme sociale / mécanisme',
    auscgie_compliance: 'Conformité AUSCGIE',
    security_law_compliance: 'Conformité droit des sûretés',
    statutes_coherence: 'Cohérence avec les statuts',
    organ_competence: 'Compétence des organes sociaux',
    title_mechanism_validity: 'Validité du mécanisme de titres',
    corporate_decisions_existence: 'Décisions sociales requises',
    formalities_completeness: 'Formalités applicables',
    rdc_financial_regulation: 'Réglementation financière et de crédit RDC',
    foreign_investor_restrictions: 'Restrictions investisseurs étrangers',
    instrument_convertibility: "Convertibilité de l'instrument",
    other: 'Autre contrôle juridique',
};

function checkTypeLabel(type: string): string {
    return financialCheckTypeLabel[type] ?? legalCheckTypeLabel[type] ?? type;
}

// Palette maison, sobre et non générique — les statuts engageants
// sont les seuls signalés en couleur (signal), les opposés en
// inversion d'encre, le reste reste neutre.
const tonePositive = 'bg-signal text-signal-ink';
const toneNegative = 'bg-ink text-paper';
const toneNeutral = 'bg-surface-2 text-ink-muted';

function termStatusTone(status: string): string {
    if (status === 'explicitly_accepted' || status === 'formalized') return tonePositive;
    if (status === 'explicitly_refused' || status === 'contradictory') return toneNegative;
    return toneNeutral;
}

function clauseStatusTone(status: string): string {
    if (status === 'accepted_by_both') return tonePositive;
    if (status === 'refused') return toneNegative;
    return toneNeutral;
}

function snapshotStatusTone(status: string): string {
    if (status === 'completed') return tonePositive;
    if (status === 'failed') return toneNegative;
    return toneNeutral;
}

function documentStatusTone(status: string): string {
    if (status === 'signed' || status === 'approved') return tonePositive;
    if (status === 'cancelled') return toneNegative;
    return toneNeutral;
}

function conditionStatusTone(status: string): string {
    if (status === 'fulfilled') return tonePositive;
    if (status === 'not_fulfilled') return toneNegative;
    return toneNeutral;
}

function signatureStatusTone(status: string): string {
    if (status === 'completed') return tonePositive;
    if (status === 'refused' || status === 'void') return toneNegative;
    return toneNeutral;
}

function checkStatusTone(status: string): string {
    if (status === 'passed') return tonePositive;
    if (status === 'failed') return toneNegative;
    return toneNeutral;
}

function checkSeverityTone(severity: string): string {
    if (severity === 'critical') return toneNegative;
    return toneNeutral;
}

function formatMoney(amountMinor: number, currency: string): string {
    return `${(amountMinor / 100).toLocaleString('fr-FR')} ${currency}`;
}

function formatBps(bps: number): string {
    return `${(bps / 100).toLocaleString('fr-FR', { maximumFractionDigits: 2 })} %`;
}

function formatDate(iso: string | null): string {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('fr-FR', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

/**
 * Saisie humaine (unités majeures, virgule acceptée) → mineurs.
 * Une saisie non numérique part brute : le serveur la refuse avec
 * son propre message — jamais de montant fantaisiste passé muet.
 */
function parseAmountToMinor(input: string): number | string {
    const normalized = input.replace(/\s/g, '').replace(',', '.');
    if (!/^\d+(\.\d{1,2})?$/.test(normalized)) {
        return input;
    }

    return Math.round(Number(normalized) * 100);
}

/**
 * Pourcentage saisi (%) → points de base [0..10000].
 */
function parsePercentToBps(input: string): number | string {
    const normalized = input.replace(/\s/g, '').replace(',', '.');
    if (!/^\d+(\.\d{1,2})?$/.test(normalized)) {
        return input;
    }

    return Math.round(Number(normalized) * 100);
}

// ---------------------------------------------------------------------------

type ActionProps = {
    workspace: WorkspaceData;
    canMutate: boolean;
    processingKey: string | null;
    onAction: (
        url: string,
        key: string,
        data?: Record<string, FormDataConvertible>,
    ) => void;
};

function ConsentSection({
    workspace,
    onAction,
    processingKey,
}: ActionProps) {
    const sides = [
        {
            role: 'entrepreneur',
            name: workspace.entrepreneurName,
            consentAt: workspace.entrepreneurConsentAt,
        },
        {
            role: 'investor',
            name: workspace.investorName,
            consentAt: workspace.investorConsentAt,
        },
    ];
    const ownConsentAt =
        workspace.viewerRole === 'entrepreneur'
            ? workspace.entrepreneurConsentAt
            : workspace.viewerRole === 'investor'
                ? workspace.investorConsentAt
                : null;
    const closed = workspace.status === 'closed' || workspace.status === 'cancelled';

    return (
        <div className="border border-rule p-5">
            <h2 className="mb-4 text-section-label">
                Analyse IA de la conversation — consentement
            </h2>
            <p className="mb-4 text-lg text-ink-muted">
                L'analyse des messages échangés exige le consentement explicite
                et séparé des DEUX parties — elle ne s'exécute jamais juste
                pour une partie. Consentir n'empêche jamais la négociation
                humaine de continuer.
            </p>
            <div className="mb-4 flex flex-col gap-2">
                {sides.map((side) => (
                    <div
                        key={side.role}
                        className="flex items-center justify-between border-b border-rule py-2 text-lg last:border-b-0"
                    >
                        <span>
                            {side.name}{' '}
                            <span className="font-mono text-lg text-ink-muted uppercase">
                                {side.role === 'entrepreneur'
                                    ? 'entrepreneur'
                                    : 'investisseur'}
                            </span>
                        </span>
                        {side.consentAt ? (
                            <Badge className={tonePositive}>
                                ✓ consenté
                            </Badge>
                        ) : (
                            <Badge variant="outline">en attente</Badge>
                        )}
                    </div>
                ))}
            </div>
            {workspace.viewerRole !== 'observer' && !ownConsentAt && !closed && (
                <Button
                    variant="cta"
                    size="sm"
                    disabled={processingKey === 'consent'}
                    onClick={() =>
                        onAction(
                            `/contract-workspaces/${workspace.id}/consent`,
                            'consent',
                        )
                    }
                >
                    Je consent à l'analyse de la conversation
                </Button>
            )}
            {workspace.viewerRole !== 'observer' &&
                ownConsentAt !== null &&
                !workspace.bothConsented && (
                    <p className="text-lg text-ink-muted">
                        Votre consentement est enregistré — l'analyse démarre
                        dès que l'autre partie consent.
                    </p>
                )}
            {workspace.viewerRole === 'observer' && (
                <p className="text-lg text-ink-muted">
                    Vous suivez cette opération en observateur : seul le lancement
                    de l'analyse vous reste inaccessible.
                </p>
            )}
            {closed && (
                <p className="text-lg text-ink-muted">
                    Opération clôturée : plus de consentement ni d'analyse
                    possible.
                </p>
            )}
        </div>
    );
}

function AnalysisSection({
    workspace,
    onAction,
    processingKey,
}: ActionProps) {
    const latest = workspace.snapshots[0];
    const closed =
        workspace.status === 'closed' || workspace.status === 'cancelled';

    return (
        <div className="border border-rule p-5">
            <h2 className="mb-4 text-section-label">Synthèse IA</h2>

            {!workspace.bothConsented ? (
                <p className="text-lg text-ink-muted">
                    L'analyse est verrouillée tant que les deux parties
                    n'ont pas consenti.
                </p>
            ) : (
                <p className="mb-4 text-lg text-ink-muted">
                    Chaque analyse produit un instantané versionné qui référence
                    exactement les messages utilisés — chaque extrait remonte
                    à sa source.
                </p>
            )}

            <div className="flex flex-col gap-6">
                {workspace.snapshots.map((snapshot) => (
                    <div key={snapshot.id} className="border border-rule p-4">
                        <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <Badge className={snapshotStatusTone(snapshot.status)}>
                                {snapshotStatusLabel[snapshot.status] ?? snapshot.status}
                            </Badge>
                            <span className="font-mono text-lg text-ink-muted uppercase">
                                v{snapshot.version} ·{' '}
                                {snapshot.messageCount === null
                                    ? 'couverture inconnue'
                                    : `${snapshot.messageCount} messages couverts`}
                                {snapshot.tokensUsed !== null &&
                                    ` · ${snapshot.tokensUsed.toLocaleString('fr-FR')} jetons`}
                            </span>
                        </div>

                        {snapshot.status === 'failed' && (
                            <div className="mb-3 border border-rule-strong bg-surface-2 px-3 py-2 text-lg text-ink">
                                {snapshot.failureReason ??
                                    "L'analyse IA n'a pas abouti — relancez."}
                            </div>
                        )}

                        {snapshot.completedAt && (
                            <div className="mb-3 font-mono text-lg text-ink-muted">
                                Terminée le {formatDate(snapshot.completedAt)}
                            </div>
                        )}

                        {snapshot.synthesis?.summary && (
                            <p className="mb-3 text-lg leading-6 text-ink">
                                {snapshot.synthesis.summary}
                            </p>
                        )}

                        {snapshot.synthesis?.counts && (
                            <div className="mb-3 flex flex-wrap gap-4 font-mono text-lg text-ink-muted uppercase">
                                <span>
                                    {snapshot.synthesis.counts.terms_kept ?? 0}{' '}
                                    conditions extraites
                                </span>
                                <span>
                                    {snapshot.synthesis.counts.terms_with_conflicts ?? 0}{' '}
                                    contradictions signalées
                                </span>
                                <span>
                                    {snapshot.synthesis.counts.clause_recommendations_kept ?? 0}{' '}
                                    clauses proposées
                                </span>
                                <span>
                                    {snapshot.synthesis.counts.clause_recommendations_ignored ?? 0}{' '}
                                    recommandations ignorées
                                </span>
                            </div>
                        )}

                        {snapshot.synthesis?.ignored_recommendations &&
                            snapshot.synthesis.ignored_recommendations.length > 0 && (
                                <details className="mb-3">
                                    <summary className="cursor-pointer font-mono text-lg text-action uppercase underline decoration-1 underline-offset-4 hover:decoration-2">
                                        Voir les recommandations ignorées
                                    </summary>
                                    <ul className="mt-2 flex list-disc flex-col gap-1 pl-4 text-lg text-ink-muted">
                                        {snapshot.synthesis.ignored_recommendations.map(
                                            (reason) => (
                                                <li key={reason}>{reason}</li>
                                            ),
                                        )}
                                    </ul>
                                </details>
                            )}
                    </div>
                ))}
            </div>

            {workspace.bothConsented && !closed && (
                <Button
                    variant={latest?.status === 'failed' ? 'cta' : 'outline'}
                    size="sm"
                    disabled={processingKey === 'analyze'}
                    onClick={() =>
                        onAction(
                            `/contract-workspaces/${workspace.id}/ai-analysis`,
                            'analyze',
                        )
                    }
                    className="mt-4 flex items-center gap-1.5"
                >
                    {latest?.status === 'failed' && <RiAlertLine size={14} />}
                    <RiSparklingLine size={14} />
                    {latest
                        ? 'Relancer l\'analyse de la négociation'
                        : 'Lancer l\'analyse de la négociation'}
                </Button>
            )}
            {workspace.bothConsented && closed && (
                <p className="mt-4 flex items-center gap-1.5 text-lg text-ink-muted">
                    <RiRefreshLine size={14} /> Relance impossible sur une
                    opération clôturée ou annulée.
                </p>
            )}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Structure — affichage + formulaire de saisie du bloc complet
// (montants, répartition, composantes). Remplacement intégral :
// la structure est vue comme un tout cohérent, jamais patchée.
// ---------------------------------------------------------------------------

type ComponentDraft = {
    type: ComponentTypeValue | string;
    equityMechanism: string;
    amountMajor: string;
    isPrimary: boolean;
};

function StructureSection({
    workspace,
    canMutate,
    processingKey,
    onAction,
}: ActionProps) {
    const emptyFields = workspace.preMoneyMinor === null
        && workspace.postMoneyMinor === null
        && workspace.totalInvestmentMinor === null;

    const [preMoneyMajor, setPreMoneyMajor] = useState(
        emptyFields ? '' : String(workspace.preMoneyMinor! / 100),
    );
    const [postMoneyMajor, setPostMoneyMajor] = useState(
        emptyFields ? '' : String(workspace.postMoneyMinor! / 100),
    );
    const [totalInvestmentMajor, setTotalInvestmentMajor] = useState(
        emptyFields ? '' : String(workspace.totalInvestmentMinor! / 100),
    );
    const [founderSharePct, setFounderSharePct] = useState(
        workspace.founderShareBps === null ? '' : String(workspace.founderShareBps / 100),
    );
    const [investorSharePct, setInvestorSharePct] = useState(
        workspace.investorShareBps === null ? '' : String(workspace.investorShareBps / 100),
    );
    const [components, setComponents] = useState<ComponentDraft[]>(
        workspace.financingComponents.map((component) => ({
            type: component.type,
            equityMechanism: '',
            amountMajor:
                component.amountMinor === null ? '' : String(component.amountMinor / 100),
            isPrimary: component.isPrimary,
        })),
    );

    function updateComponent(index: number, patch: Partial<ComponentDraft>) {
        setComponents((list) =>
            list.map((component, position) =>
                position === index ? { ...component, ...patch } : component,
            ),
        );
    }

    function submit() {
        const payload: Record<string, FormDataConvertible> = {};

        if (preMoneyMajor !== '') {
            payload.pre_money_minor = parseAmountToMinor(preMoneyMajor);
        }
        if (postMoneyMajor !== '') {
            payload.post_money_minor = parseAmountToMinor(postMoneyMajor);
        }
        if (totalInvestmentMajor !== '') {
            payload.total_investment_minor = parseAmountToMinor(totalInvestmentMajor);
        }
        if (founderSharePct !== '') {
            payload.founder_share_bps = parsePercentToBps(founderSharePct);
        }
        if (investorSharePct !== '') {
            payload.investor_share_bps = parsePercentToBps(investorSharePct);
        }
        if (components.length > 0) {
            payload.components = components.map((component) => ({
                type: component.type,
                ...(component.type === 'capital' && component.equityMechanism !== ''
                    ? { equity_mechanism: component.equityMechanism }
                    : {}),
                amount_minor: parseAmountToMinor(component.amountMajor),
                currency: workspace.currency,
                ...(component.isPrimary ? { is_primary: true } : {}),
            }));
        }

        onAction(
            `/contract-workspaces/${workspace.id}/structure`,
            'structure',
            payload,
        );
    }

    return (
        <div className="border border-rule p-5">
            <h2 className="mb-4 text-section-label">Structure de l'opération</h2>
            <div className="mb-4 flex flex-wrap gap-6 text-lg">
                {workspace.totalInvestmentMinor !== null && (
                    <div>
                        <div className="font-mono text-lg tracking-wide text-ink-muted uppercase">
                            Montant global
                        </div>
                        <div className="font-mono text-lg font-bold text-ink">
                            {formatMoney(
                                workspace.totalInvestmentMinor,
                                workspace.currency,
                            )}
                        </div>
                    </div>
                )}
                {workspace.preMoneyMinor !== null && (
                    <div>
                        <div className="font-mono text-lg tracking-wide text-ink-muted uppercase">
                            Pré-money
                        </div>
                        <div className="font-mono text-lg text-ink">
                            {formatMoney(workspace.preMoneyMinor, workspace.currency)}
                        </div>
                    </div>
                )}
                {workspace.postMoneyMinor !== null && (
                    <div>
                        <div className="font-mono text-lg tracking-wide text-ink-muted uppercase">
                            Post-money
                        </div>
                        <div className="font-mono text-lg text-ink">
                            {formatMoney(workspace.postMoneyMinor, workspace.currency)}
                        </div>
                    </div>
                )}
                {workspace.founderShareBps !== null && (
                    <div>
                        <div className="font-mono text-lg tracking-wide text-ink-muted uppercase">
                            Parts fondateurs
                        </div>
                        <div className="font-mono text-lg text-ink">
                            {formatBps(workspace.founderShareBps)}
                        </div>
                    </div>
                )}
                {workspace.investorShareBps !== null && (
                    <div>
                        <div className="font-mono text-lg tracking-wide text-ink-muted uppercase">
                            Parts investisseur
                        </div>
                        <div className="font-mono text-lg text-ink">
                            {formatBps(workspace.investorShareBps)}
                        </div>
                    </div>
                )}
            </div>
            {workspace.financingComponents.length > 0 && (
                <div className="mb-4 flex flex-col gap-2">
                    {workspace.financingComponents.map((component) => (
                        <div
                            key={component.id}
                            className="flex items-center justify-between border-b border-rule py-2 text-lg last:border-b-0"
                        >
                            <span>
                                {componentTypeLabel[component.type] ?? component.type}
                                {component.isPrimary && (
                                    <span className="font-mono text-lg text-ink-muted uppercase">
                                        {' '}
                                        · principal
                                    </span>
                                )}
                            </span>
                            <span className="font-mono text-lg text-ink-muted">
                                {component.amountMinor !== null &&
                                    formatMoney(component.amountMinor, component.currency)}
                            </span>
                        </div>
                    ))}
                    <p className="text-lg text-ink-muted">
                        La dette n'est jamais dilutive : elle n'entre dans aucun
                        calcul de valuation ni de répartition de capital.
                    </p>
                </div>
            )}

            {canMutate && (
                <div className="border border-rule p-4">
                    <p className="mb-3 font-mono text-lg text-ink-muted uppercase">
                        Saisir / mettre à jour la structure (bloc complet)
                    </p>
                    <div className="mb-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label className="flex flex-col gap-1 text-lg">
                            <span className="font-mono text-ink-muted uppercase">
                                Pré-money ({workspace.currency})
                            </span>
                            <Input
                                size="sm"
                                inputMode="decimal"
                                suffix={workspace.currency}
                                value={preMoneyMajor}
                                onChange={(e) => setPreMoneyMajor(e.target.value)}
                            />
                        </label>
                        <label className="flex flex-col gap-1 text-lg">
                            <span className="font-mono text-ink-muted uppercase">
                                Post-money ({workspace.currency})
                            </span>
                            <Input
                                size="sm"
                                inputMode="decimal"
                                suffix={workspace.currency}
                                value={postMoneyMajor}
                                onChange={(e) => setPostMoneyMajor(e.target.value)}
                            />
                        </label>
                        <label className="flex flex-col gap-1 text-lg">
                            <span className="font-mono text-ink-muted uppercase">
                                Montant global ({workspace.currency})
                            </span>
                            <Input
                                size="sm"
                                inputMode="decimal"
                                suffix={workspace.currency}
                                value={totalInvestmentMajor}
                                onChange={(e) => setTotalInvestmentMajor(e.target.value)}
                            />
                        </label>
                        <div className="grid grid-cols-2 gap-3">
                            <label className="flex flex-col gap-1 text-lg">
                                <span className="font-mono text-ink-muted uppercase">
                                    Parts fondateurs (%)
                                </span>
                                <Input
                                    size="sm"
                                    inputMode="decimal"
                                    suffix="%"
                                    value={founderSharePct}
                                    onChange={(e) => setFounderSharePct(e.target.value)}
                                />
                            </label>
                            <label className="flex flex-col gap-1 text-lg">
                                <span className="font-mono text-ink-muted uppercase">
                                    Parts investisseur (%)
                                </span>
                                <Input
                                    size="sm"
                                    inputMode="decimal"
                                    suffix="%"
                                    value={investorSharePct}
                                    onChange={(e) => setInvestorSharePct(e.target.value)}
                                />
                            </label>
                        </div>
                    </div>

                    <p className="mb-2 font-mono text-lg text-ink-muted uppercase">
                        Composantes de financement
                    </p>
                    <div className="flex flex-col gap-3">
                        {components.map((component, index) => (
                            <div key={index} className="grid grid-cols-1 gap-2 border border-rule p-3 sm:grid-cols-4">
                                <label className="flex flex-col gap-1 text-lg">
                                    <span className="font-mono text-ink-muted uppercase">
                                        Instrument
                                    </span>
                                    <Select
                                        size="sm"
                                        value={component.type}
                                        onChange={(e) =>
                                            updateComponent(index, { type: e.target.value })
                                        }
                                    >
                                        <option value="capital">Capital</option>
                                        <option value="debt">Dette</option>
                                        <option value="convertible">Instrument convertible</option>
                                    </Select>
                                </label>
                                <label className="flex flex-col gap-1 text-lg">
                                    <span className="font-mono text-ink-muted uppercase">
                                        Mécanisme (capital seul)
                                    </span>
                                    <Select
                                        size="sm"
                                        disabled={component.type !== 'capital'}
                                        value={component.equityMechanism}
                                        onChange={(e) =>
                                            updateComponent(index, {
                                                equityMechanism: e.target.value,
                                            })
                                        }
                                    >
                                        <option value="">— à préciser —</option>
                                        <option value="capital_increase">
                                            Augmentation de capital
                                        </option>
                                        <option value="title_transfer">Cession de titres</option>
                                    </Select>
                                </label>
                                <label className="flex flex-col gap-1 text-lg">
                                    <span className="font-mono text-ink-muted uppercase">
                                        Montant ({workspace.currency})
                                    </span>
                                    <Input
                                        size="sm"
                                        inputMode="decimal"
                                        suffix={workspace.currency}
                                        value={component.amountMajor}
                                        onChange={(e) =>
                                            updateComponent(index, {
                                                amountMajor: e.target.value,
                                            })
                                        }
                                    />
                                </label>
                                <div className="flex items-center justify-between gap-2">
                                    <Checkbox
                                        size="sm"
                                        checked={component.isPrimary}
                                        label="Principal"
                                        onChange={(e) =>
                                            updateComponent(index, {
                                                isPrimary: e.target.checked,
                                            })
                                        }
                                    />
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        aria-label="Retirer cette composante"
                                        onClick={() =>
                                            setComponents((list) =>
                                                list.filter(
                                                    (_, position) => position !== index,
                                                ),
                                            )
                                        }
                                    >
                                        <RiDeleteBinLine size={16} />
                                    </Button>
                                </div>
                            </div>
                        ))}
                        <Button
                            variant="outline"
                            size="sm"
                            className="flex items-center gap-1.5 self-start"
                            onClick={() =>
                                setComponents((list) => [
                                    ...list,
                                    {
                                        type: 'capital',
                                        equityMechanism: '',
                                        amountMajor: '',
                                        isPrimary: false,
                                    },
                                ])
                            }
                        >
                            <RiAddLine size={14} /> Ajouter une composante
                        </Button>
                    </div>

                    <Button
                        variant="cta"
                        size="sm"
                        disabled={processingKey === 'structure'}
                        onClick={submit}
                        className="mt-4"
                    >
                        Enregistrer la structure
                    </Button>
                    <p className="mt-2 text-lg text-ink-muted">
                        L'enregistrement remplace la liste complète des composantes.
                        Les contrôles financiers (montants, pourcentages,
                        double comptage) sont exécutés déterministiquement lors
                        de la passe dédiée ci-dessous.
                    </p>
                </div>
            )}
        </div>
    );
}

function CapTableSnapshotSection({ workspace }: { workspace: WorkspaceData }) {
    const snapshot = workspace.postOperationCapTable;

    if (!snapshot) {
        return null;
    }

    return (
        <div className="border border-rule p-5">
            <h2 className="mb-4 text-section-label">
                Table de capitalisation après opération
            </h2>
            <div className="mb-4 flex flex-wrap gap-4 font-mono text-lg text-ink-muted uppercase">
                {snapshot.pricePerShareMinor !== null && (
                    <span>
                        Prix par titre :{' '}
                        {formatMoney(snapshot.pricePerShareMinor, snapshot.currency ?? workspace.currency)}
                        {snapshot.pricePerShareExact === false && ' (arrondi)'}
                    </span>
                )}
                {snapshot.investorShareBps !== null && (
                    <span>Participation investisseur : {formatBps(snapshot.investorShareBps)}</span>
                )}
                {snapshot.founderShareBps !== null && (
                    <span>Parts fondateurs : {formatBps(snapshot.founderShareBps)}</span>
                )}
                {snapshot.targetInvestorShareBps !== null && (
                    <Badge
                        className={
                            snapshot.targetAchievable ? tonePositive : toneNegative
                        }
                    >
                        Participation cible{' '}
                        {snapshot.targetAchievable ? 'atteinte' : 'non atteinte'}
                    </Badge>
                )}
            </div>

            <div className="flex flex-col gap-1">
                {snapshot.entries.map((entry, index) => (
                    <div
                        key={index}
                        className="flex flex-wrap items-center justify-between gap-2 border-b border-rule py-2 text-lg last:border-b-0"
                    >
                        <span>
                            {entry.holderName ?? '—'}
                            {entry.isInvestor && (
                                <span className="ml-1 font-mono text-lg text-ink-muted uppercase">
                                    (investisseur)
                                </span>
                            )}
                        </span>
                        <span className="font-mono text-lg text-ink-muted">
                            {entry.percentageAfterBps !== null &&
                                formatBps(entry.percentageAfterBps)}
                            {' · '}
                            {entry.sharesAfter !== null &&
                                `${entry.sharesAfter.toLocaleString('fr-FR')} titres`}
                        </span>
                    </div>
                ))}
            </div>

            {snapshot.warnings.length > 0 && (
                <div className="mt-3 border border-rule-strong bg-surface-2 px-3 py-2 text-lg text-ink">
                    <div className="mb-1 flex items-center gap-1.5 font-mono uppercase">
                        <RiAlertLine size={14} /> Contraintes de calcul signalées
                    </div>
                    <ul className="list-disc pl-4">
                        {snapshot.warnings.map((warning) => (
                            <li key={warning}>{warning}</li>
                        ))}
                    </ul>
                </div>
            )}

            <p className="mt-3 text-lg text-ink-muted">
                Participation calculée par le moteur déterministe — la dette
                n'y figure jamais : seuls les instruments donnant accès au
                capital y participent.
            </p>
        </div>
    );
}

// Passe combinée : une seule relance serveur exécute les deux
// lots (financier `fin-…` et juridique `leg-…`) — la page
// n'interprète rien, elle affiche chaque contrôle du dernier
// batch de chaque famille.
function ChecksSection({
    workspace,
    canMutate,
    processingKey,
    onAction,
}: ActionProps) {
    const closed = workspace.status === 'closed' || workspace.status === 'cancelled';
    const hasAnything =
        workspace.financialChecks.length > 0 || workspace.legalChecks.length > 0;

    return (
        <div className="border border-rule p-5">
            <h2 className="mb-4 text-section-label">
                Contrôles financiers et juridiques (dernière passe)
            </h2>
            <p className="mb-4 text-lg text-ink-muted">
                Les contrôles sont déterministes — aucun traitement n'est délégué
                à l'IA. Une anomalie CRITIQUE non résolue fige la finalisation ;
                une relance crée un nouveau lot sans effacer l'historique.
            </p>

            {!hasAnything ? (
                <p className="mb-4 text-lg text-ink-muted">
                    Aucun contrôle exécuté pour l'instant — lancez la passe
                    après avoir saisi la structure.
                </p>
            ) : (
                <div className="mb-4 flex flex-col gap-3">
                    {(workspace.hasBlockingFinancialCheck ||
                        workspace.hasBlockingLegalCheck) && (
                        <div className="flex items-center gap-1.5 border border-rule-strong bg-surface-2 px-3 py-2 text-lg text-ink">
                            <RiAlertLine size={14} /> Une anomalie critique
                            non résolue bloque la clôture.
                        </div>
                    )}
                    {[workspace.financialChecks, workspace.legalChecks].map(
                        (pass, passIndex) =>
                            pass.length > 0 && (
                                <div key={passIndex} className="flex flex-col gap-3">
                                    <p className="font-mono text-lg text-ink-muted uppercase">
                                        {passIndex === 0
                                            ? 'contrôles financiers'
                                            : 'contrôles juridiques'}
                                    </p>
                                    {pass.map((check) => (
                                        <div key={check.id} className="border border-rule p-4">
                                            <div className="mb-2 flex flex-wrap items-center gap-2">
                                                <Badge className={checkSeverityTone(check.severity)}>
                                                    {checkSeverityLabel[check.severity] ?? check.severity}
                                                </Badge>
                                                <Badge className={checkStatusTone(check.status)}>
                                                    {checkStatusLabel[check.status] ?? check.status}
                                                </Badge>
                                                <span className="text-lg font-bold text-ink">
                                                    {check.title}
                                                </span>
                                                {check.resolvedAt && (
                                                    <Badge variant="outline">résolu le {formatDate(check.resolvedAt)}</Badge>
                                                )}
                                            </div>
                                            <div className="mb-1 flex flex-wrap gap-3 font-mono text-lg text-ink-muted uppercase">
                                                <span>{checkTypeLabel(check.type)}</span>
                                            </div>
                                            <p className="text-lg text-ink-muted">{check.message}</p>
                                        </div>
                                    ))}
                                </div>
                            ),
                    )}
                </div>
            )}

            {canMutate && (
                <Button
                    variant="outline"
                    size="sm"
                    disabled={processingKey === 'financial-checks'}
                    onClick={() =>
                        onAction(
                            `/contract-workspaces/${workspace.id}/financial-checks`,
                            'financial-checks',
                        )
                    }
                    className="flex items-center gap-1.5"
                >
                    <RiRefreshLine size={14} />
                    {hasAnything ? 'Relancer les contrôles' : 'Lancer les contrôles'}
                </Button>
            )}
            {canMutate && closed && (
                <p className="text-lg text-ink-muted">
                    Opération terminée : les contrôles restent consultables
                    en historique.
                </p>
            )}
        </div>
    );
}

function TermsSection({ workspace }: { workspace: WorkspaceData }) {
    if (workspace.terms.length === 0) {
        return (
            <div className="border border-rule p-5">
                <h2 className="mb-4 text-section-label">Conditions négociées</h2>
                <p className="text-lg text-ink-muted">
                    Aucune condition n'a encore été extraite de la conversation —
                    lancez l'analyse IA ou attendez la prochaine interrogation.
                </p>
            </div>
        );
    }

    return (
        <div className="border border-rule p-5">
            <h2 className="mb-4 text-section-label">Conditions négociées</h2>
            <div className="flex flex-col gap-4">
                {workspace.terms.map((term) => {
                    const context = term.sourceContext;
                    const authorName =
                        typeof context?.author_name === 'string'
                            ? context.author_name
                            : null;
                    const authorRole =
                        typeof context?.author_role === 'string'
                            ? context.author_role
                            : null;
                    const postedAt =
                        typeof context?.posted_at === 'string'
                            ? context.posted_at
                            : null;
                    const extract =
                        typeof context?.extract === 'string'
                            ? context.extract
                            : null;

                    return (
                        <div key={term.id} className="border border-rule p-4">
                            <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
                                <span className="font-bold text-ink">
                                    {term.label}
                                </span>
                                <Badge className={termStatusTone(term.status)}>
                                    {termStatusLabel[term.status] ?? term.status}
                                </Badge>
                            </div>

                            <div className="mb-2 flex flex-wrap gap-3 font-mono text-lg text-ink-muted uppercase">
                                <span>{term.subject}</span>
                                {term.instrument && (
                                    <span>
                                        instrument :{' '}
                                        {componentTypeLabel[term.instrument] ?? term.instrument}
                                    </span>
                                )}
                                <span>
                                    {termCategoryLabel[term.category] ?? term.category}
                                </span>
                                <span>{termKindLabel[term.kind] ?? term.kind}</span>
                            </div>

                            {term.displayValue && (
                                <div className="mb-2 font-mono text-lg font-bold text-ink">
                                    {term.displayValue}
                                </div>
                            )}

                            {term.notes && (
                                <p className="mb-2 text-lg text-ink-muted">
                                    {term.notes}
                                </p>
                            )}

                            {term.conflictWithTermId && (
                                <p className="mb-2 flex items-center gap-1.5 text-lg text-ink">
                                    <RiAlertLine size={14} /> Version divergente de
                                    la condition #{term.conflictWithTermId} — les
                                    deux restent tracées, aucune n'est arbitrée.
                                </p>
                            )}

                            {extract && (
                                <pre className="max-h-40 overflow-y-auto border border-rule bg-surface-2 p-3 text-lg whitespace-pre-wrap text-ink-muted">
                                    {authorRole && (
                                        <>
                                            {authorName ?? 'Partie'} (
                                            {authorRole}
                                            {postedAt ? `, le ${formatDate(postedAt)}` : ''})
                                            :
                                        </>
                                    )}
                                    {'\n'}« {extract} »
                                </pre>
                            )}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Clauses — propositions (bibliothèque ou personnalisation) et cycle
// de décision alterné. La turn-guard est appliquée côté serveur :
// l'interface propose, le serveur tranche.
// ---------------------------------------------------------------------------

function ClauseProposeForm({
    workspace,
    canMutate,
    processingKey,
    onAction,
}: ActionProps) {
    const [source, setSource] = useState<'library' | 'custom'>('library');
    const [libraryId, setLibraryId] = useState<string>(
        workspace.clauseLibrary[0]?.id ? String(workspace.clauseLibrary[0].id) : '',
    );
    const [variant, setVariant] = useState('');
    const [title, setTitle] = useState('');
    const [body, setBody] = useState('');
    const [rationale, setRationale] = useState('');

    const selectedLibraryEntry = workspace.clauseLibrary.find(
        (entry) => String(entry.id) === libraryId,
    );

    function submit() {
        const payload: Record<string, FormDataConvertible> = { source };
        if (source === 'library') {
            payload.library_id = libraryId;
            if (variant !== '') {
                payload.variant = variant;
            }
        } else {
            payload.title = title;
            payload.body = body;
        }
        if (rationale !== '') {
            payload.rationale = rationale;
        }

        onAction(
            `/contract-workspaces/${workspace.id}/clauses`,
            'clause-propose',
            payload,
        );
    }

    if (!canMutate) {
        return null;
    }

    return (
        <div className="mt-4 border border-rule p-4">
            <p className="mb-3 font-mono text-lg text-ink-muted uppercase">
                Proposer une clause
            </p>
            <div className="mb-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <label className="flex flex-col gap-1 text-lg">
                    <span className="font-mono text-ink-muted uppercase">Source</span>
                    <Select
                        size="sm"
                        value={source}
                        onChange={(e) =>
                            setSource(e.target.value as 'library' | 'custom')
                        }
                    >
                        <option value="library">Bibliothèque structurée</option>
                        <option value="custom">Personnalisée</option>
                    </Select>
                </label>
                {source === 'library' && (
                    <label className="flex flex-col gap-1 text-lg">
                        <span className="font-mono text-ink-muted uppercase">
                            Clause de la bibliothèque
                        </span>
                        <Select
                            size="sm"
                            value={libraryId}
                            onChange={(e) => {
                                setLibraryId(e.target.value);
                                setVariant('');
                            }}
                        >
                            {workspace.clauseLibrary.length === 0 && (
                                <option value="">Bibliothèque vide</option>
                            )}
                            {workspace.clauseLibrary.map((entry) => (
                                <option key={entry.id} value={entry.id}>
                                    {entry.title}
                                </option>
                            ))}
                        </Select>
                    </label>
                )}
                {source === 'library' &&
                    (selectedLibraryEntry?.variants.length ?? 0) > 0 && (
                        <label className="flex flex-col gap-1 text-lg">
                            <span className="font-mono text-ink-muted uppercase">
                                Variante (optionnelle)
                            </span>
                            <Select
                                size="sm"
                                value={variant}
                                onChange={(e) => setVariant(e.target.value)}
                            >
                                <option value="">Texte de base</option>
                                {selectedLibraryEntry?.variants.map((option) => (
                                    <option key={option} value={option}>
                                        {option}
                                    </option>
                                ))}
                            </Select>
                        </label>
                    )}
            </div>
            {source === 'custom' && (
                <div className="mb-3 flex flex-col gap-3">
                    <label className="flex flex-col gap-1 text-lg">
                        <span className="font-mono text-ink-muted uppercase">Titre</span>
                        <Input
                            size="sm"
                            icon={<RiFileTextLine size={18} />}
                            value={title}
                            onChange={(e) => setTitle(e.target.value)}
                        />
                    </label>
                    <label className="flex flex-col gap-1 text-lg">
                        <span className="font-mono text-ink-muted uppercase">
                            Texte contractuel
                        </span>
                        <Textarea
                            rows={5}
                            value={body}
                            onChange={(e) => setBody(e.target.value)}
                        />
                    </label>
                </div>
            )}
            <label className="mb-3 flex flex-col gap-1 text-lg">
                <span className="font-mono text-ink-muted uppercase">
                    Justification (optionnelle)
                </span>
                <Textarea
                    rows={2}
                    value={rationale}
                    onChange={(e) => setRationale(e.target.value)}
                />
            </label>
            <p className="mb-3 text-lg text-ink-muted">
                Le texte d'une clause de bibliothèque est copié tel quel —
                jamais réécrit. L'origine (fondateur / investisseur) est déduite
                de votre côté, jamais déclarée.
            </p>
            <Button
                variant="cta"
                size="sm"
                disabled={
                    processingKey === 'clause-propose'
                    || (source === 'custom' && (title === '' || body === ''))
                }
                onClick={submit}
            >
                Proposer la clause
            </Button>
        </div>
    );
}

function ClauseCard({
    workspace,
    clause,
    canMutate,
    processingKey,
    onAction,
}: ActionProps & { clause: WorkspaceData['clauses'][number] }) {
    const terminal =
        clause.status === 'accepted_by_both' || clause.status === 'refused';
    const [decision, setDecision] = useState<ClauseDecisionValue | null>(null);
    const [counterBody, setCounterBody] = useState('');
    const [comment, setComment] = useState('');
    const [localError, setLocalError] = useState<string | null>(null);

    function submit(chosen: ClauseDecisionValue) {
        if (chosen === 'counter_proposal' && counterBody.trim() === '') {
            setLocalError(
                'Une contre-proposition porte un nouveau texte — écrivez-le avant d\'envoyer.',
            );

            return;
        }

        setLocalError(null);

        const payload: Record<string, FormDataConvertible> = { decision: chosen };
        if (chosen === 'counter_proposal') {
            payload.body = counterBody;
        }
        if (comment !== '') {
            payload.comment = comment;
        }

        onAction(
            `/contract-workspaces/${workspace.id}/clauses/${clause.id}/decision`,
            `clause-decide-${clause.id}`,
            payload,
        );
    }

    return (
        <div className="border border-rule p-4">
            <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
                <span className="font-bold text-ink">{clause.title}</span>
                <Badge className={clauseStatusTone(clause.status)}>
                    {clauseStatusLabel[clause.status] ?? clause.status}
                </Badge>
            </div>

            <div className="mb-2 flex flex-wrap gap-3 font-mono text-lg text-ink-muted uppercase">
                <span>
                    {clauseOriginLabel[clause.origin] ?? clause.origin}
                </span>
                {clause.instrumentScope && (
                    <span>
                        portée :{' '}
                        {componentTypeLabel[clause.instrumentScope] ??
                            clause.instrumentScope}
                    </span>
                )}
                {clause.variant && <span>variante : {clause.variant}</span>}
                <span>
                    {clauseCategoryLabel[clause.category] ?? clause.category}
                </span>
            </div>

            {(clause.founderDecision || clause.investorDecision) && (
                <div className="mb-2 flex flex-wrap gap-3 text-lg">
                    {clause.founderDecision && (
                        <span>
                            Position fondateur :{' '}
                            <span className="font-mono">
                                {clauseDecisionLabel[clause.founderDecision] ??
                                    clause.founderDecision}
                            </span>
                        </span>
                    )}
                    {clause.investorDecision && (
                        <span>
                            Position investisseur :{' '}
                            <span className="font-mono">
                                {clauseDecisionLabel[clause.investorDecision] ??
                                    clause.investorDecision}
                            </span>
                        </span>
                    )}
                    <span className="text-ink-muted">
                        (dernier mot de chaque partie, alternance stricte)
                    </span>
                </div>
            )}

            {clause.rationale && (
                <p className="mb-2 text-lg text-ink-muted">
                    {clause.rationale}
                </p>
            )}

            {clause.body && (
                <details>
                    <summary className="cursor-pointer font-mono text-lg text-action uppercase underline decoration-1 underline-offset-4 hover:decoration-2">
                        Voir le texte contractuel
                    </summary>
                    <pre className="mt-2 max-h-72 overflow-y-auto border border-rule bg-surface-2 p-3 text-lg whitespace-pre-wrap text-ink">
                        {clause.body}
                    </pre>
                </details>
            )}

            {canMutate && !terminal && (
                <div className="mt-3 border border-rule p-3">
                    <p className="mb-2 font-mono text-lg text-ink-muted uppercase">
                        Votre décision
                    </p>
                    <div className="flex flex-wrap gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={processingKey === `clause-decide-${clause.id}`}
                            onClick={() => submit('accept')}
                        >
                            Accepter
                        </Button>
                        <Button
                            variant="destructive-outline"
                            size="sm"
                            disabled={processingKey === `clause-decide-${clause.id}`}
                            onClick={() => submit('refuse')}
                        >
                            Refuser
                        </Button>
                        <Button
                            variant="secondary"
                            size="sm"
                            disabled={processingKey === `clause-decide-${clause.id}`}
                            onClick={() => submit('request_revision')}
                        >
                            Demander une révision
                        </Button>
                        {decision === null ? (
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => setDecision('counter_proposal')}
                            >
                                Contre-proposition…
                            </Button>
                        ) : (
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => {
                                    setDecision(null);
                                    setCounterBody('');
                                }}
                            >
                                <RiDeleteBinLine size={14} /> Annuler la contre-proposition
                            </Button>
                        )}
                    </div>
                    {decision === 'counter_proposal' && (
                        <div className="mt-3 flex flex-col gap-2">
                            <Textarea
                                rows={4}
                                placeholder="Nouveau texte proposé"
                                value={counterBody}
                                onChange={(e) => setCounterBody(e.target.value)}
                            />
                            <Button
                                variant="cta"
                                size="sm"
                                disabled={processingKey === `clause-decide-${clause.id}`}
                                onClick={() => submit('counter_proposal')}
                            >
                                Envoyer la contre-proposition
                            </Button>
                        </div>
                    )}
                    <label className="mt-3 flex flex-col gap-1 text-lg">
                        <span className="font-mono text-ink-muted uppercase">
                            Commentaire (optionnel, joint à la décision)
                        </span>
                        <Textarea
                            rows={2}
                            value={comment}
                            onChange={(e) => setComment(e.target.value)}
                        />
                    </label>
                    {localError && (
                        <p className="mt-2 border border-rule-strong bg-surface-2 px-3 py-2 text-lg text-ink">
                            {localError}
                        </p>
                    )}
                </div>
            )}
            {terminal && (
                <p className="mt-3 text-lg text-ink-muted">
                    Clause définitivement{' '}
                    {clause.status === 'accepted_by_both' ? 'acceptée' : 'refusée'} —
                    plus de décision possible.
                </p>
            )}
        </div>
    );
}

function ClausesSection({
    workspace,
    canMutate,
    processingKey,
    onAction,
}: ActionProps) {
    return (
        <div className="border border-rule p-5">
            <h2 className="mb-4 text-section-label">Clauses proposées</h2>

            {workspace.clauses.length === 0 ? (
                <p className="mb-2 text-lg text-ink-muted">
                    Aucune clause sélectionnée pour l'instant — l'IA ne
                    recommande que des clauses réelles de la bibliothèque
                    structurée, et les parties décident.
                </p>
            ) : (
                <div className="flex flex-col gap-4">
                    {workspace.clauses.map((clause) => (
                        <ClauseCard
                            key={clause.id}
                            workspace={workspace}
                            clause={clause}
                            canMutate={canMutate}
                            processingKey={processingKey}
                            onAction={onAction}
                        />
                    ))}
                </div>
            )}

            <ClauseProposeForm
                workspace={workspace}
                canMutate={canMutate}
                processingKey={processingKey}
                onAction={onAction}
            />
        </div>
    );
}

// ---------------------------------------------------------------------------
// Documents — dépôt, double approbation commerciale, validation juridique
// (users.manage), demande de signatures, dépôt de l'exemplaire signé.
// ---------------------------------------------------------------------------

function DocumentCreateForm({
    workspace,
    canMutate,
    processingKey,
    onAction,
}: ActionProps) {
    const [type, setType] = useState<DocumentTypeValue>('letter_of_intent');
    const [title, setTitle] = useState('');
    const [confidentiality, setConfidentiality] = useState<ConfidentialityValue>('shared');
    const [contentText, setContentText] = useState('');

    if (!canMutate) {
        return null;
    }

    function submit() {
        const content: Record<string, string> = {};
        if (contentText !== '') {
            content.text = contentText;
        }

        onAction(
            `/contract-workspaces/${workspace.id}/documents`,
            'document-create',
            {
                type,
                title,
                confidentiality,
                content,
            },
        );
    }

    return (
        <div className="mt-4 border border-rule p-4">
            <p className="mb-3 font-mono text-lg text-ink-muted uppercase">
                Déposer un document
            </p>
            <div className="mb-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <label className="flex flex-col gap-1 text-lg">
                    <span className="font-mono text-ink-muted uppercase">Type</span>
                    <Select
                        size="sm"
                        value={type}
                        onChange={(e) => setType(e.target.value as DocumentTypeValue)}
                    >
                        {(Object.keys(documentTypeLabel) as DocumentTypeValue[]).map(
                            (value) => (
                                <option key={value} value={value}>
                                    {documentTypeLabel[value] ?? value}
                                </option>
                            ),
                        )}
                    </Select>
                </label>
                <label className="flex flex-col gap-1 text-lg">
                    <span className="font-mono text-ink-muted uppercase">
                        Confidentialité
                    </span>
                    <Select
                        size="sm"
                        value={confidentiality}
                        onChange={(e) =>
                            setConfidentiality(e.target.value as ConfidentialityValue)
                        }
                    >
                        <option value="shared">Partagé</option>
                        <option value="founder_only">Visible du fondateur seul</option>
                        <option value="investor_only">
                            Visible de l'investisseur seul
                        </option>
                    </Select>
                </label>
            </div>
            <label className="mb-3 flex flex-col gap-1 text-lg">
                <span className="font-mono text-ink-muted uppercase">Titre</span>
                <Input
                    size="sm"
                    icon={<RiFileTextLine size={18} />}
                    value={title}
                    onChange={(e) => setTitle(e.target.value)}
                />
            </label>
            <label className="mb-3 flex flex-col gap-1 text-lg">
                <span className="font-mono text-ink-muted uppercase">
                    Contenu (notes ou texte du document)
                </span>
                <Textarea
                    rows={4}
                    value={contentText}
                    onChange={(e) => setContentText(e.target.value)}
                />
            </label>
            <p className="mb-3 text-lg text-ink-muted">
                Le dépôt crée la version 1 avec l'empreinte SHA-256 du contenu
                exact — les versions et l'empreinte restent inaltérables.
            </p>
            <Button
                variant="cta"
                size="sm"
                disabled={processingKey === 'document-create' || title.trim().length < 3}
                onClick={submit}
            >
                Déposer le document
            </Button>
        </div>
    );
}

function DocumentCard({
    workspace,
    document,
    canMutate,
    canLegalValidate,
    processingKey,
    onAction,
}: ActionProps & {
    document: WorkspaceData['documents'][number];
    canLegalValidate: boolean;
}) {
    const finalState =
        document.status === 'signed'
        || document.status === 'archived'
        || document.status === 'cancelled';
    const bothApproved =
        document.founderApprovedAt !== null && document.investorApprovedAt !== null;
    const canRequestSignatures = bothApproved && document.status !== 'signed';
    const canDepositSigned =
        bothApproved
        && document.legalValidatedAt !== null
        && (document.status === 'approved' || document.status === 'pending_signature');

    return (
        <div className="border border-rule p-4">
            <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
                <span className="font-bold text-ink">{document.title}</span>
                <Badge className={documentStatusTone(document.status)}>
                    {documentStatusLabel[document.status] ?? document.status}
                </Badge>
            </div>
            <div className="mb-2 flex flex-wrap gap-3 font-mono text-lg text-ink-muted uppercase">
                <span>{documentTypeLabel[document.type] ?? document.type}</span>
                {document.currentVersion !== null && (
                    <span>v{document.currentVersion}</span>
                )}
                <span>
                    {confidentialityLabel[document.confidentiality] ?? document.confidentiality}
                </span>
            </div>

            <div className="mb-2 flex flex-col gap-1 text-lg">
                <span>
                    Approbation fondateur :{' '}
                    {document.founderApprovedAt
                        ? formatDate(document.founderApprovedAt)
                        : 'en attente'}
                </span>
                <span>
                    Approbation investisseur :{' '}
                    {document.investorApprovedAt
                        ? formatDate(document.investorApprovedAt)
                        : 'en attente'}
                </span>
                <span>
                    Validation juridique :{' '}
                    {document.legalValidatedAt
                        ? formatDate(document.legalValidatedAt)
                        : 'en attente'}
                </span>
                {document.signedDepositedAt && (
                    <span>
                        Exemplaire signé déposé le{' '}
                        {formatDate(document.signedDepositedAt)}
                    </span>
                )}
            </div>

            {(canMutate || canLegalValidate) && !finalState && (
                <div className="mt-3 flex flex-wrap gap-2">
                    {canMutate && !bothApproved && (
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={processingKey === `document-approve-${document.id}`}
                            onClick={() =>
                                onAction(
                                    `/contract-workspaces/${workspace.id}/documents/${document.id}/approve`,
                                    `document-approve-${document.id}`,
                                )
                            }
                        >
                            Approuver ce document
                        </Button>
                    )}
                    {canMutate && bothApproved && canRequestSignatures && (
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={processingKey === `document-sign-${document.id}`}
                            onClick={() =>
                                onAction(
                                    `/contract-workspaces/${workspace.id}/documents/${document.id}/signatures`,
                                    `document-sign-${document.id}`,
                                )
                            }
                        >
                            Demander les signatures
                        </Button>
                    )}
                    {canLegalValidate && document.legalValidatedAt === null && (
                        <Button
                            variant="secondary"
                            size="sm"
                            disabled={processingKey === `document-legal-${document.id}`}
                            onClick={() =>
                                onAction(
                                    `/contract-workspaces/${workspace.id}/documents/${document.id}/legal-validate`,
                                    `document-legal-${document.id}`,
                                )
                            }
                            className="flex items-center gap-1.5"
                        >
                            <RiShieldCheckLine size={14} /> Valider juridiquement
                        </Button>
                    )}
                    {canMutate && canDepositSigned && (
                        <Button
                            variant="cta"
                            size="sm"
                            disabled={processingKey === `document-deposit-${document.id}`}
                            onClick={() =>
                                onAction(
                                    `/contract-workspaces/${workspace.id}/documents/${document.id}/signed-deposit`,
                                    `document-deposit-${document.id}`,
                                )
                            }
                        >
                            Déposer l'exemplaire signé
                        </Button>
                    )}
                </div>
            )}
        </div>
    );
}

function DocumentsSection({
    workspace,
    canMutate,
    canLegalValidate,
    processingKey,
    onAction,
}: ActionProps & { canLegalValidate: boolean }) {
    const closed = workspace.status === 'closed' || workspace.status === 'cancelled';

    return (
        <div className="border border-rule p-5">
            <h2 className="mb-4 text-section-label">Documents contractuels</h2>
            <p className="mb-4 text-lg text-ink-muted">
                Chaque document suit un double circuit : approbation par les
                DEUX parties, puis validation juridique — seul un document
                entièrement validé peut se voir déclarer signé.
            </p>

            {closed && (
                <p className="mb-4 text-lg text-ink-muted">
                    Opération clôturée ou annulée : les documents restent
                    consultables, en état final.
                </p>
            )}

            {workspace.documents.length === 0 && (
                <p className="mb-2 text-lg text-ink-muted">
                    Aucun document déposé pour l'instant.
                </p>
            )}

            <div className="flex flex-col gap-4">
                {workspace.documents.map((document) => (
                    <DocumentCard
                        key={document.id}
                        workspace={workspace}
                        document={document}
                        canMutate={canMutate}
                        canLegalValidate={canLegalValidate}
                        processingKey={processingKey}
                        onAction={onAction}
                    />
                ))}
            </div>

            {!closed && (
                <DocumentCreateForm
                    workspace={workspace}
                    canMutate={canMutate}
                    processingKey={processingKey}
                    onAction={onAction}
                />
            )}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Conditions — suspensive × résolutive, obligations, covenants. Toute
// condition en attente bloque la clôture.
// ---------------------------------------------------------------------------

function ConditionCreateForm({
    workspace,
    canMutate,
    processingKey,
    onAction,
}: ActionProps) {
    const [type, setType] = useState<ConditionTypeValue>('suspensive');
    const [responsibleParty, setResponsibleParty] = useState<PartyRoleValue>('mutual');
    const [description, setDescription] = useState('');
    const [dueDate, setDueDate] = useState('');

    if (!canMutate) {
        return null;
    }

    function submit() {
        onAction(
            `/contract-workspaces/${workspace.id}/conditions`,
            'condition-create',
            {
                type,
                responsible_party: responsibleParty,
                description,
                ...(dueDate !== '' ? { due_date: dueDate } : {}),
            },
        );
    }

    return (
        <div className="mt-4 border border-rule p-4">
            <p className="mb-3 font-mono text-lg text-ink-muted uppercase">
                Ajouter une condition / obligation / engagement
            </p>
            <div className="mb-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <label className="flex flex-col gap-1 text-lg">
                    <span className="font-mono text-ink-muted uppercase">Type</span>
                    <Select
                        size="sm"
                        value={type}
                        onChange={(e) => setType(e.target.value as ConditionTypeValue)}
                    >
                        {(Object.keys(conditionTypeLabel) as ConditionTypeValue[]).map(
                            (value) => (
                                <option key={value} value={value}>
                                    {conditionTypeLabel[value] ?? value}
                                </option>
                            ),
                        )}
                    </Select>
                </label>
                <label className="flex flex-col gap-1 text-lg">
                    <span className="font-mono text-ink-muted uppercase">
                        Partie responsable
                    </span>
                    <Select
                        size="sm"
                        value={responsibleParty}
                        onChange={(e) =>
                            setResponsibleParty(e.target.value as PartyRoleValue)
                        }
                    >
                        <option value="mutual">Commune aux deux parties</option>
                        <option value="founder">Fondateur</option>
                        <option value="investor">Investisseur</option>
                        <option value="company">Société</option>
                    </Select>
                </label>
                <label className="flex flex-col gap-1 text-lg">
                    <span className="font-mono text-ink-muted uppercase">
                        Échéance (optionnelle)
                    </span>
                    <Input
                        size="sm"
                        icon={<RiCalendarLine size={18} />}
                        type="date"
                        value={dueDate}
                        onChange={(e) => setDueDate(e.target.value)}
                    />
                </label>
            </div>
            <label className="mb-3 flex flex-col gap-1 text-lg">
                <span className="font-mono text-ink-muted uppercase">Description</span>
                <Textarea
                    rows={3}
                    value={description}
                    onChange={(e) => setDescription(e.target.value)}
                />
            </label>
            <p className="mb-3 text-lg text-ink-muted">
                Une condition est créée « en attente » : elle bloque la clôture
                tant qu'elle n'est pas conduite à terme, marquée non réalisée
                ou explicitement levée — jamais estimée d'avance.
            </p>
            <Button
                variant="cta"
                size="sm"
                disabled={processingKey === 'condition-create' || description.trim().length < 5}
                onClick={submit}
            >
                Enregistrer la condition
            </Button>
        </div>
    );
}

function ConditionsSection({
    workspace,
    canMutate,
    processingKey,
    onAction,
}: ActionProps) {
    const closed = workspace.status === 'closed' || workspace.status === 'cancelled';

    return (
        <div className="border border-rule p-5">
            <h2 className="mb-4 text-section-label">Conditions & engagements</h2>

            {workspace.conditions.length === 0 && (
                <p className="text-lg text-ink-muted">
                    Aucune condition enregistrée.
                </p>
            )}

            <div className="flex flex-col gap-4">
                {workspace.conditions.map((condition) => (
                    <div key={condition.id} className="border border-rule p-4">
                        <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
                            <span className="font-bold text-ink">
                                {conditionTypeLabel[condition.type] ?? condition.type}
                            </span>
                            <Badge className={conditionStatusTone(condition.status)}>
                                {conditionStatusLabel[condition.status] ?? condition.status}
                            </Badge>
                        </div>
                        <div className="mb-2 flex flex-wrap gap-3 font-mono text-lg text-ink-muted uppercase">
                            <span>
                                {partyRoleLabel[condition.responsibleParty]
                                    ?? condition.responsibleParty}
                            </span>
                            {condition.dueDate && (
                                <span>échéance : {condition.dueDate}</span>
                            )}
                        </div>
                        <p className="mb-2 text-lg text-ink">
                            {condition.description}
                        </p>
                        {condition.fulfilledAt && (
                            <p className="mb-2 flex items-center gap-1.5 text-lg text-ink-muted">
                                <RiCheckboxCircleLine size={14} /> Conduite à
                                terme le {formatDate(condition.fulfilledAt)}
                            </p>
                        )}
                        {canMutate && condition.status === 'pending' && (
                            <div className="mt-2 flex flex-wrap gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={processingKey === `condition-resolve-${condition.id}`}
                                    onClick={() =>
                                        onAction(
                                            `/contract-workspaces/${workspace.id}/conditions/${condition.id}/resolve`,
                                            `condition-resolve-${condition.id}`,
                                            { decision: 'fulfilled' },
                                        )
                                    }
                                >
                                    Conduite à terme
                                </Button>
                                <Button
                                    variant="destructive-outline"
                                    size="sm"
                                    disabled={processingKey === `condition-resolve-${condition.id}`}
                                    onClick={() =>
                                        onAction(
                                            `/contract-workspaces/${workspace.id}/conditions/${condition.id}/resolve`,
                                            `condition-resolve-${condition.id}`,
                                            { decision: 'not_fulfilled' },
                                        )
                                    }
                                >
                                    Non réalisée
                                </Button>
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    disabled={processingKey === `condition-resolve-${condition.id}`}
                                    onClick={() =>
                                        onAction(
                                            `/contract-workspaces/${workspace.id}/conditions/${condition.id}/resolve`,
                                            `condition-resolve-${condition.id}`,
                                            { decision: 'waived' },
                                        )
                                    }
                                >
                                    Lever explicitement
                                </Button>
                            </div>
                        )}
                    </div>
                ))}
            </div>

            {!closed && (
                <ConditionCreateForm
                    workspace={workspace}
                    canMutate={canMutate}
                    processingKey={processingKey}
                    onAction={onAction}
                />
            )}
            {closed && workspace.conditions.some(
                (condition) => condition.status === 'pending',
            ) && (
                <p className="mt-4 flex items-center gap-1.5 text-lg text-ink">
                    <RiAlertLine size={14} /> Des conditions restent en attente
                    sur une opération terminée — tracez-les dans l'historique.
                </p>
            )}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Signatures — une demande par signataire et par version ; la preuve
// est celle du prestataire ou du dépôt des exemplaires, jamais une
// validation d'interface.
// ---------------------------------------------------------------------------

function SignatureCard({
    workspace,
    signature,
    documentTitle,
    canMutate,
    processingKey,
    onAction,
}: ActionProps & { signature: WorkspaceData['signatures'][number]; documentTitle: string | null }) {
    const [evidence, setEvidence] = useState('');

    function submit() {
        onAction(
            `/contract-workspaces/${workspace.id}/signatures/${signature.id}/deposit`,
            `signature-deposit-${signature.id}`,
            { signature_evidence: { description: evidence } },
        );
    }

    return (
        <div className="border border-rule p-4">
            <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
                <span className="font-bold text-ink">
                    {signature.signerRole === 'founder'
                        ? workspace.entrepreneurName
                        : workspace.investorName}{' '}
                    <span className="font-mono text-lg text-ink-muted uppercase">
                        ({signature.signerRole === 'founder' ? 'fondateur' : 'investisseur'})
                    </span>
                </span>
                <Badge className={signatureStatusTone(signature.status)}>
                    {signatureStatusLabel[signature.status] ?? signature.status}
                </Badge>
            </div>
            <div className="mb-2 flex flex-wrap gap-3 font-mono text-lg text-ink-muted uppercase">
                <span>
                    {signatureMethodLabel[signature.method] ?? signature.method}
                </span>
                {documentTitle && <span>document : {documentTitle}</span>}
                {signature.signedAt && <span>le {formatDate(signature.signedAt)}</span>}
            </div>
            {signature.hasEvidence && (
                <p className="text-lg text-ink-muted">
                    ✓ Preuve enregistrée.
                </p>
            )}
            {canMutate && signature.status === 'requested' && (
                <div className="mt-2 flex flex-col gap-2">
                    <label className="flex flex-col gap-1 text-lg">
                        <span className="font-mono text-ink-muted uppercase">
                            Preuve de signature (référence de l'exemplaire
                            signé ou du certificat du prestataire)
                        </span>
                        <Input
                            size="sm"
                            icon={<RiFileCopy2Line size={18} />}
                            value={evidence}
                            onChange={(e) => setEvidence(e.target.value)}
                        />
                    </label>
                    <Button
                        variant="outline"
                        size="sm"
                        className="self-start"
                        disabled={processingKey === `signature-deposit-${signature.id}`}
                        onClick={submit}
                    >
                        Enregistrer la preuve de signature
                    </Button>
                </div>
            )}
        </div>
    );
}

function SignaturesSection({
    workspace,
    canMutate,
    processingKey,
    onAction,
}: ActionProps) {
    if (workspace.signatures.length === 0) {
        return (
            <div className="border border-rule p-5">
                <h2 className="mb-4 text-section-label">Signatures</h2>
                <p className="text-lg text-ink-muted">
                    Aucune signature demandée — les demandes partent d'un
                    document approuvé par les deux parties.
                </p>
            </div>
        );
    }

    const documentTitleFor = (documentId: number) => {
        const found = workspace.documents.find(
            (document) => document.id === documentId,
        );

        return found?.title ?? null;
    };

    return (
        <div className="border border-rule p-5">
            <h2 className="mb-4 text-section-label">Signatures</h2>
            <div className="mb-4 flex flex-col gap-4">
                {workspace.signatures.map((signature) => (
                    <SignatureCard
                        key={signature.id}
                        workspace={workspace}
                        signature={signature}
                        documentTitle={documentTitleFor(signature.documentId)}
                        canMutate={canMutate}
                        processingKey={processingKey}
                        onAction={onAction}
                    />
                ))}
            </div>
            <p className="text-lg text-ink-muted">
                Quand toutes les signatures d'une version sont complétées, le
                document bascule automatiquement en « signé » avec horodatage
                de dépôt.
            </p>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Revue juridique, clôture et annulation — trois portes
// déterministes, énoncées à l'identique côté serveur.
// ---------------------------------------------------------------------------

function ClosingSection({
    workspace,
    canMutate,
    canLegalValidate,
    processingKey,
    onAction,
}: ActionProps & { canLegalValidate: boolean }) {
    const closed = workspace.status === 'closed' || workspace.status === 'cancelled';
    const pendingConditions = workspace.conditions.filter(
        (condition) => condition.status === 'pending',
    ).length;

    return (
        <div className="border border-rule p-5">
            <h2 className="mb-4 text-section-label">
                Revue juridique, clôture et annulation
            </h2>

            {closed ? (
                <p className="flex items-center gap-1.5 text-lg text-ink">
                    <RiLockLine size={14} />{' '}
                    {workspace.status === 'closed'
                        ? 'Opération clôturée — tous les échanges contractuels sont figés.'
                        : 'Opération annulée — état terminal, aucun acte possible.'}
                </p>
            ) : (
                <>
                    <ul className="mb-4 flex flex-col gap-2 text-lg">
                        <li>
                            {workspace.hasBlockingFinancialCheck ? '✗' : '✓'}{' '}
                            Aucun contrôle financier critique non résolu
                        </li>
                        <li>
                            {workspace.hasBlockingLegalCheck ? '✗' : '✓'}{' '}
                            Aucun contrôle juridique critique non résolu
                        </li>
                        <li>
                            {pendingConditions > 0 ? '✗' : '✓'}{' '}
                            {pendingConditions > 0
                                ? `${pendingConditions} condition(s) en attente`
                                : 'Toutes les conditions sont conduites'}
                        </li>
                        <li>
                            {workspace.legalReviewedAt ? '✓' : '✗'}{' '}
                            Revue juridique de l'opération{' '}
                            {workspace.legalReviewedAt
                                ? `datée du ${formatDate(workspace.legalReviewedAt)}`
                                : 'non conduite'}
                        </li>
                    </ul>

                    {canLegalValidate && !workspace.legalReviewedAt && (
                        <Button
                            variant="secondary"
                            size="sm"
                            className="mb-4 flex items-center gap-1.5"
                            disabled={processingKey === 'legal-review'}
                            onClick={() =>
                                onAction(
                                    `/contract-workspaces/${workspace.id}/legal-review`,
                                    'legal-review',
                                )
                            }
                        >
                            <RiShieldCheckLine size={14} /> Conduire la revue
                            juridique de l'opération
                        </Button>
                    )}
                    {!canLegalValidate && !workspace.legalReviewedAt && (
                        <p className="mb-4 text-lg text-ink-muted">
                            La revue juridique reste réservée à la gestion
                            juridique (users.manage) — un juriste n'est pas un
                            participant de l'opération.
                        </p>
                    )}

                    {canMutate && (
                        <div className="flex flex-wrap gap-2">
                            <Button
                                variant="cta"
                                size="sm"
                                disabled={processingKey === 'close'}
                                onClick={() =>
                                    onAction(
                                        `/contract-workspaces/${workspace.id}/close`,
                                        'close',
                                    )
                                }
                            >
                                Clôturer l'opération
                            </Button>
                            <Button
                                variant="destructive-outline"
                                size="sm"
                                disabled={processingKey === 'cancel'}
                                onClick={() =>
                                    onAction(
                                        `/contract-workspaces/${workspace.id}/cancel`,
                                        'cancel',
                                    )
                                }
                            >
                                Annuler l'opération
                            </Button>
                        </div>
                    )}
                </>
            )}
        </div>
    );
}

// ---------------------------------------------------------------------------

export default function Workspace({
    workspace,
}: {
    workspace: WorkspaceData;
}) {
    const { props } = usePage<SharedProps>();
    const [processingKey, setProcessingKey] = useState<string | null>(null);
    const firstError = Object.values(props.errors ?? {})[0] ?? null;
    const successMessage = props.flash?.success ?? null;

    const terminated =
        workspace.status === 'closed' || workspace.status === 'cancelled';
    const canMutate = workspace.viewerRole !== 'observer' && !terminated;
    const canLegalValidate =
        props.auth?.permissions?.includes('users.manage') ?? false;

    function onAction(
        url: string,
        key: string,
        data: Record<string, FormDataConvertible> = {},
    ) {
        setProcessingKey(key);
        router.post(url, data, {
            preserveScroll: true,
            onFinish: () => setProcessingKey(null),
        });
    }

    return (
        <div className="mx-auto max-w-4xl px-6 py-10">
            <Head title={`Espace contractuel — ${workspace.projectTitle}`} />

            <InertiaLink
                href={`/deal-rooms/${workspace.dealRoomId}`}
                className="mb-4 inline-block font-mono text-lg text-ink-muted hover:text-ink"
            >
                ← Deal Room
            </InertiaLink>

            <div className="mb-6 border-b border-rule pb-6">
                <p className="text-eyebrow">Espace contractuel</p>
                <h1 className="mt-1 mb-2 text-page-title">
                    <InertiaLink
                        href={`/projects/${workspace.projectSlug}`}
                        className="hover:underline"
                    >
                        {workspace.projectTitle}
                    </InertiaLink>
                </h1>
                <p className="mb-3 text-body">
                    {workspace.entrepreneurName} × {workspace.investorName}
                    {workspace.legalFormLabel && (
                        <span className="text-ink-muted">
                            {' '}
                            · {workspace.legalFormLabel}
                        </span>
                    )}
                </p>
                <div className="flex flex-wrap items-center gap-2">
                    <Badge className="bg-surface-2 text-ink">
                        {workspaceStatusLabel[workspace.status] ?? workspace.status}
                    </Badge>
                    {workspace.financingCategory && (
                        <Badge variant="outline">
                            {financingCategoryLabel[workspace.financingCategory] ??
                                workspace.financingCategory}
                        </Badge>
                    )}
                    <Badge variant="outline">
                        <span className="font-mono">{workspace.currency}</span>
                    </Badge>
                    <span className="font-mono text-lg text-ink-muted">
                        dernière activité : {formatDate(workspace.lastActivityAt)}
                    </span>
                </div>
            </div>

            {successMessage && (
                <div className="mb-6 border border-rule bg-surface-2 px-3 py-2 text-lg text-ink">
                    {successMessage}
                </div>
            )}
            {firstError && (
                <div className="mb-6 border border-rule-strong bg-surface-2 px-3 py-2 text-lg text-ink">
                    {firstError}
                </div>
            )}

            <div className="flex flex-col gap-6">
                <ConsentSection
                    workspace={workspace}
                    canMutate={canMutate}
                    processingKey={processingKey}
                    onAction={onAction}
                />
                <AnalysisSection
                    workspace={workspace}
                    canMutate={canMutate}
                    processingKey={processingKey}
                    onAction={onAction}
                />
                <StructureSection
                    workspace={workspace}
                    canMutate={canMutate}
                    processingKey={processingKey}
                    onAction={onAction}
                />
                <CapTableSnapshotSection workspace={workspace} />
                <ChecksSection
                    workspace={workspace}
                    canMutate={canMutate}
                    processingKey={processingKey}
                    onAction={onAction}
                />
                <TermsSection workspace={workspace} />
                <ClausesSection
                    workspace={workspace}
                    canMutate={canMutate}
                    processingKey={processingKey}
                    onAction={onAction}
                />
                <DocumentsSection
                    workspace={workspace}
                    canMutate={canMutate}
                    canLegalValidate={canLegalValidate}
                    processingKey={processingKey}
                    onAction={onAction}
                />
                <ConditionsSection
                    workspace={workspace}
                    canMutate={canMutate}
                    processingKey={processingKey}
                    onAction={onAction}
                />
                <SignaturesSection
                    workspace={workspace}
                    canMutate={canMutate}
                    processingKey={processingKey}
                    onAction={onAction}
                />
                <ClosingSection
                    workspace={workspace}
                    canMutate={canMutate}
                    canLegalValidate={canLegalValidate}
                    processingKey={processingKey}
                    onAction={onAction}
                />
            </div>
        </div>
    );
}
