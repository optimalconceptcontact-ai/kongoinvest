import { Head, useForm } from '@inertiajs/react';
import {
    RiArrowRightUpLine,
    RiBuildingLine,
    RiCheckboxCircleLine,
    RiErrorWarningLine,
    RiLightbulbLine,
    RiScalesLine,
    RiTeamLine,
} from '@remixicon/react';
import { useState, type FormEvent } from 'react';
import { BackLink } from '@/components/ui/BackLink';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/Field';
import { FieldError } from '@/components/ui/FieldError';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/Label';

type Props = {
    project: {
        id: number;
        title: string;
        slug: string;
        stage: string;
        stageLabel: string;
        identityStatus: string;
        businessStatus: string;
        businessStatusLabel: string;
        complianceStatus: string;
        fundingTargetMinor: number;
    };
    readiness: {
        id: number;
        intendedLegalForm: string | null;
        intendedCompanyName: string | null;
        intendedPurpose: string | null;
        intendedOffice: string | null;
        intendedCapitalMinor: number;
        indicativeCofounders: {
            name: string;
            email: string;
            role: string;
            projected_share_bps: number;
            is_lead: boolean;
        }[];
        recommendedLegalForm: string | null;
        recommendationRationale: string | null;
        readinessScore: number;
        readinessChecklist: Record<string, boolean> | null;
        isConverted: boolean;
    } | null;
    eligibility: {
        allowed_actions: string[];
        blocked_actions: string[];
        reasons: string[];
    };
};

export default function PreIncorporationReadiness({ project, readiness, eligibility }: Props) {
    const [showConvertModal, setShowConvertModal] = useState(false);

    // Readiness update form
    const form = useForm<{
        maturity_stage: string;
        intended_legal_form: string;
        intended_company_name: string;
        intended_corporate_purpose: string;
        intended_registered_office: string;
        intended_capital_minor: number;
        indicative_cofounders: {
            name: string;
            email: string;
            role: string;
            projected_share_bps: number;
            is_lead: boolean;
        }[];
    }>({
        maturity_stage: project.stage,
        intended_legal_form: readiness?.intendedLegalForm ?? 'sarl',
        intended_company_name: readiness?.intendedCompanyName ?? project.title,
        intended_corporate_purpose: readiness?.intendedPurpose ?? '',
        intended_registered_office: readiness?.intendedOffice ?? 'Kinshasa, RDC',
        intended_capital_minor: readiness?.intendedCapitalMinor ?? 100000,
        indicative_cofounders: readiness?.indicativeCofounders ?? [
            { name: '', email: '', role: 'Lead Founder', projected_share_bps: 10000, is_lead: true },
        ],
    });

    // Conversion form
    const convertForm = useForm<{
        company_name: string;
        rccm_number: string;
        legal_form: string;
        tax_identification_number: string;
        city: string;
    }>({
        company_name: readiness?.intendedCompanyName ?? project.title,
        rccm_number: '',
        legal_form: readiness?.intendedLegalForm ?? 'sarl',
        tax_identification_number: '',
        city: 'Kinshasa',
    });

    function submitReadiness(e: FormEvent) {
        e.preventDefault();
        form.put(`/projects/${project.id}/readiness`);
    }

    function submitConversion(e: FormEvent) {
        e.preventDefault();
        convertForm.post(`/projects/${project.id}/convert-to-company`);
    }

    function addCofounder() {
        form.setData('indicative_cofounders', [
            ...form.data.indicative_cofounders,
            { name: '', email: '', role: 'Co-fondateur', projected_share_bps: 2000, is_lead: false },
        ]);
    }

    return (
        <div className="mx-auto max-w-4xl px-6 py-12">
            <Head title={`Structuration & Readiness — ${project.title}`} />
            <BackLink href={`/projects/${project.slug}`} />
            <p className="text-eyebrow">Pre-incorporation & Structuration OHADA</p>
            <h1 className="mt-2 mb-2 text-page-title flex items-center gap-3">
                <RiLightbulbLine size={28} className="text-action" />
                Maturation du Projet & Structuration Juridique
            </h1>
            <p className="mb-6 text-body">
                KongoInvest accueille les projets dès le stade d'idée. La présentation d'un RCCM n'est jamais obligatoire
                avant la constitution définitive, permettant un accès immédiat à l'Investment Readiness et au matching préliminaire.
            </p>

            {/* Statuses Grid (Section 19.9) */}
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                <div className="border border-rule bg-surface p-3">
                    <span className="text-xs uppercase text-ink-muted">Stade du projet</span>
                    <div className="text-sm font-bold text-ink mt-1">{project.stageLabel}</div>
                </div>
                <div className="border border-rule bg-surface p-3">
                    <span className="text-xs uppercase text-ink-muted">Identité Porteur</span>
                    <div className="text-sm font-bold text-action mt-1 capitalize">{project.identityStatus}</div>
                </div>
                <div className="border border-rule bg-surface p-3">
                    <span className="text-xs uppercase text-ink-muted">Personnalité Morale</span>
                    <div className="text-sm font-bold text-ink mt-1">{project.businessStatusLabel}</div>
                </div>
                <div className="border border-rule bg-surface p-3">
                    <span className="text-xs uppercase text-ink-muted">Conformité Matching</span>
                    <div className="text-xs font-mono font-bold text-action mt-1">{project.complianceStatus}</div>
                </div>
            </div>

            {/* Legal Eligibility Summary (Section 19.8) */}
            <div className="mb-8 border border-rule bg-surface p-6 shadow-xs">
                <h2 className="text-base font-bold text-ink mb-4 flex items-center gap-2">
                    <RiScalesLine size={20} className="text-action" />
                    Droits & Règles d'éligibilité aux opérations financières
                </h2>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                    <div className="border-l-2 border-action pl-3 space-y-1">
                        <strong className="text-action text-xs uppercase font-mono">Actions Autorisées au stade actuel :</strong>
                        <ul className="list-disc list-inside text-ink space-y-1 text-xs">
                            <li>Publication & visibilité plateforme</li>
                            <li>Évaluation & diagnostic par KongoInvest</li>
                            <li>Coaching Investment Readiness</li>
                            <li>Manifestations d'intérêt (LOI) non contraignantes</li>
                            <li>Préparation de Term Sheet indicatif</li>
                        </ul>
                    </div>

                    <div className="border-l-2 border-signal pl-3 space-y-1">
                        <strong className="text-signal text-xs uppercase font-mono">Actions Bloquées (Sans Société Immatriculée) :</strong>
                        <ul className="list-disc list-inside text-ink-muted space-y-1 text-xs">
                            <li>Émission de parts sociales ou d'actions</li>
                            <li>Inscription définitive en Cap Table</li>
                            <li>Génération de certificat d'actions</li>
                            <li>Closing equity & décaissement de fonds</li>
                        </ul>
                    </div>
                </div>

                {project.businessStatus === 'not_incorporated' && (
                    <div className="mt-6 pt-4 border-t border-rule flex items-center justify-between">
                        <span className="text-xs text-ink-muted">Votre société est immatriculée au RCCM ?</span>
                        <Button onClick={() => setShowConvertModal(true)} size="sm">
                            <RiBuildingLine size={16} /> Renseigner le RCCM & Passer au statut Société
                        </Button>
                    </div>
                )}
            </div>

            {/* Smart Legal Recommendation Card */}
            {readiness?.recommendedLegalForm && (
                <div className="mb-8 border border-rule bg-surface-2 p-4">
                    <span className="text-xs font-mono uppercase text-action font-semibold">
                        Recommandation Juridique OHADA Adaptée :
                    </span>
                    <h3 className="font-bold text-base text-ink mt-1">
                        Forme sociale préconisée : {readiness.recommendedLegalForm.toUpperCase()}
                    </h3>
                    <p className="text-xs text-ink-muted mt-1 leading-relaxed">
                        {readiness.recommendationRationale}
                    </p>
                </div>
            )}

            {/* Form */}
            <form onSubmit={submitReadiness} className="flex flex-col gap-6 border border-rule p-6 bg-surface">
                <div className="border-b border-rule pb-2">
                    <h3 className="font-semibold text-ink text-base">Structuration & Cap Table Prévisionnelle</h3>
                    <p className="text-xs text-ink-muted">Cette répartition est indicative et ne préjuge pas de la cap table juridique définitive.</p>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Field error={!!form.errors.maturity_stage}>
                        <Label>Stade d'avancement du projet *</Label>
                        <select
                            value={form.data.maturity_stage}
                            onChange={(e) => form.setData('maturity_stage', e.target.value)}
                            className="w-full border border-rule bg-surface p-2 text-sm text-ink"
                        >
                            <option value="idea">Idée (non encore validée)</option>
                            <option value="validation">Étude de marché / Validation concept</option>
                            <option value="prototype">Prototype / Preuve de concept (PoC)</option>
                            <option value="mvp">Produit Minimum Viable (MVP)</option>
                            <option value="early_stage">Amorçage / Début commercialisation</option>
                            <option value="growth">Croissance</option>
                        </select>
                        <FieldError>{form.errors.maturity_stage}</FieldError>
                    </Field>

                    <Field error={!!form.errors.intended_legal_form}>
                        <Label>Forme sociale envisagée (OHADA) *</Label>
                        <select
                            value={form.data.intended_legal_form}
                            onChange={(e) => form.setData('intended_legal_form', e.target.value)}
                            className="w-full border border-rule bg-surface p-2 text-sm text-ink"
                        >
                            <option value="sarlu">SARLU (Unipersonnelle)</option>
                            <option value="sarl">SARL (Pluripersonnelle)</option>
                            <option value="sasu">SASU (Unipersonnelle actions)</option>
                            <option value="sas">SAS (Actions simplifiée - recommandée investisseurs)</option>
                            <option value="sa">SA (Société anonyme)</option>
                        </select>
                        <FieldError>{form.errors.intended_legal_form}</FieldError>
                    </Field>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Field error={!!form.errors.intended_company_name}>
                        <Label>Dénomination sociale envisagée *</Label>
                        <Input
                            value={form.data.intended_company_name}
                            onChange={(e) => form.setData('intended_company_name', e.target.value)}
                        />
                        <FieldError>{form.errors.intended_company_name}</FieldError>
                    </Field>

                    <Field error={!!form.errors.intended_capital_minor}>
                        <Label>Capital initial envisagé (en cents / sous-unités) *</Label>
                        <Input
                            type="number"
                            value={form.data.intended_capital_minor}
                            onChange={(e) => form.setData('intended_capital_minor', parseInt(e.target.value) || 0)}
                        />
                        <FieldError>{form.errors.intended_capital_minor}</FieldError>
                    </Field>
                </div>

                {/* Co-founders Indicative Cap Table */}
                <div className="border-t border-rule pt-4">
                    <div className="flex items-center justify-between mb-3">
                        <Label className="font-bold text-sm">Équipe Fondatrice & Répartition Prévisionnelle du Capital</Label>
                        <Button type="button" onClick={addCofounder} variant="outline" size="sm">
                            + Ajouter un cofondateur
                        </Button>
                    </div>

                    <div className="space-y-3">
                        {form.data.indicative_cofounders.map((cf, idx) => (
                            <div key={idx} className="grid grid-cols-1 sm:grid-cols-4 gap-3 p-3 border border-rule bg-surface-2 text-xs">
                                <div>
                                    <span className="font-semibold text-ink">Nom complet</span>
                                    <Input
                                        value={cf.name}
                                        onChange={(e) => {
                                            const updated = [...form.data.indicative_cofounders];
                                            updated[idx].name = e.target.value;
                                            form.setData('indicative_cofounders', updated);
                                        }}
                                        placeholder="Nom & Prénom"
                                    />
                                </div>
                                <div>
                                    <span className="font-semibold text-ink">Rôle</span>
                                    <Input
                                        value={cf.role}
                                        onChange={(e) => {
                                            const updated = [...form.data.indicative_cofounders];
                                            updated[idx].role = e.target.value;
                                            form.setData('indicative_cofounders', updated);
                                        }}
                                        placeholder="Ex: CEO, CTO..."
                                    />
                                </div>
                                <div>
                                    <span className="font-semibold text-ink">Part prévisionnelle (bps)</span>
                                    <Input
                                        type="number"
                                        value={cf.projected_share_bps}
                                        onChange={(e) => {
                                            const updated = [...form.data.indicative_cofounders];
                                            updated[idx].projected_share_bps = parseInt(e.target.value) || 0;
                                            form.setData('indicative_cofounders', updated);
                                        }}
                                    />
                                </div>
                                <div className="flex items-center gap-2 pt-4">
                                    <span className="font-mono font-bold text-ink text-sm">
                                        {(cf.projected_share_bps / 100).toFixed(1)} %
                                    </span>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                <Button type="submit" disabled={form.processing} className="self-start px-8 py-3">
                    {form.processing ? 'Enregistrement...' : 'Enregistrer la structuration'}
                </Button>
            </form>

            {/* Modal Conversion to Company */}
            {showConvertModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-lg border border-rule bg-surface p-6 shadow-lg">
                        <h3 className="text-lg font-bold text-ink mb-1">Passage en Société Immatriculée</h3>
                        <p className="text-xs text-ink-muted mb-4">
                            Cette étape rattachera votre projet à une société légale avec un numéro RCCM sans effacer votre historique.
                        </p>

                        <form onSubmit={submitConversion} className="space-y-4">
                            <Field error={!!convertForm.errors.company_name}>
                                <Label>Dénomination officielle enregistrée au RCCM *</Label>
                                <Input
                                    value={convertForm.data.company_name}
                                    onChange={(e) => convertForm.setData('company_name', e.target.value)}
                                />
                                <FieldError>{convertForm.errors.company_name}</FieldError>
                            </Field>

                            <Field error={!!convertForm.errors.rccm_number}>
                                <Label>Numéro RCCM officiel *</Label>
                                <Input
                                    value={convertForm.data.rccm_number}
                                    onChange={(e) => convertForm.setData('rccm_number', e.target.value)}
                                    placeholder="CD/KIN/RCCM/..."
                                />
                                <FieldError>{convertForm.errors.rccm_number}</FieldError>
                            </Field>

                            <div className="flex justify-end gap-3 pt-4 border-t border-rule">
                                <Button type="button" variant="outline" onClick={() => setShowConvertModal(false)}>
                                    Annuler
                                </Button>
                                <Button type="submit" disabled={convertForm.processing}>
                                    {convertForm.processing ? 'Conversion...' : 'Valider l\'immatriculation'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
