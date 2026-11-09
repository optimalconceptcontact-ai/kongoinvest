import { Head, useForm } from '@inertiajs/react';
import {
    RiBuildingLine,
    RiCheckboxCircleLine,
    RiFileTextLine,
    RiShieldCheckLine,
    RiUploadCloud2Line,
} from '@remixicon/react';
import type { FormEvent } from 'react';
import { BackLink } from '@/components/ui/BackLink';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/Field';
import { FieldError } from '@/components/ui/FieldError';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/Label';

type Props = {
    company: {
        id: number;
        name: string;
        registrationNumber: string | null;
        legalForm: string | null;
    };
    verification: {
        id: number;
        status: string;
        rccm: string | null;
        nif: string | null;
        nationalId: string | null;
        registeredOffice: string | null;
        capitalMinor: number;
        capitalCurrency: string;
        sector: string | null;
        legalRepresentative: string | null;
        verifiedAt: string | null;
        notes: string | null;
    } | null;
    documents: {
        id: number;
        type: string;
        typeLabel: string;
        title: string;
        isVerified: boolean;
        issueDate: string | null;
    }[];
};

export default function Kyb({ company, verification, documents }: Props) {
    const { data, setData, post, processing, errors } = useForm<{
        company_name: string;
        legal_form: string;
        rccm_number: string;
        tax_identification_number: string;
        national_identification_number: string;
        registered_office_address: string;
        country_of_incorporation: string;
        date_of_incorporation: string;
        corporate_purpose: string;
        share_capital_minor: number;
        share_capital_currency: string;
        main_economic_activity: string;
        industry_sector: string;
        legal_representative_name: string;
        legal_representative_title: string;
        has_sectorial_authorization: boolean;
        rccm_file: File | null;
        statutes_file: File | null;
        nif_file: File | null;
    }>({
        company_name: company.name,
        legal_form: company.legalForm ?? 'sarl',
        rccm_number: verification?.rccm ?? company.registrationNumber ?? '',
        tax_identification_number: verification?.nif ?? '',
        national_identification_number: verification?.nationalId ?? '',
        registered_office_address: verification?.registeredOffice ?? '',
        country_of_incorporation: 'CD',
        date_of_incorporation: '',
        corporate_purpose: '',
        share_capital_minor: verification?.capitalMinor ?? 100000,
        share_capital_currency: verification?.capitalCurrency ?? 'USD',
        main_economic_activity: '',
        industry_sector: verification?.sector ?? '',
        legal_representative_name: verification?.legalRepresentative ?? '',
        legal_representative_title: 'Gérant',
        has_sectorial_authorization: false,
        rccm_file: null,
        statutes_file: null,
        nif_file: null,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post(`/companies/${company.id}/kyb`, { forceFormData: true });
    }

    const isVerified = verification?.status === 'verified';

    return (
        <div className="mx-auto max-w-4xl px-6 py-12">
            <Head title={`KYB — ${company.name}`} />
            <BackLink href="/dashboard" />
            <p className="text-eyebrow">Conformité Entreprise</p>
            <h1 className="mt-2 mb-2 text-page-title flex items-center gap-3">
                <RiBuildingLine size={28} className="text-action" />
                Vérification d'Entreprise (KYB) — {company.name}
            </h1>
            <p className="mb-6 text-body">
                Identification légale des personnes morales, sociétés cibles et investisseurs corporatifs selon le droit
                des sociétés OHADA (AUSCGIE) et la réglementation AML/CFT de la RDC.
            </p>

            {/* Status Banner */}
            <div className="mb-6 flex items-center justify-between border border-rule bg-surface p-4">
                <div>
                    <span className="text-xs uppercase text-ink-muted">Statut du dossier KYB</span>
                    <div className="text-lg font-bold text-ink flex items-center gap-2 mt-1">
                        <RiShieldCheckLine size={20} className={isVerified ? 'text-action' : 'text-ink-muted'} />
                        {verification?.status ? verification.status.toUpperCase() : 'NON SOUMIS'}
                    </div>
                </div>
                {verification && (
                    <a
                        href={`/verifications/${verification.id}/beneficial-owners`}
                        className="text-sm font-mono text-action underline decoration-1 hover:decoration-2"
                    >
                        Gérer les Bénéficiaires Effectifs (UBO) →
                    </a>
                )}
            </div>

            {/* Form */}
            {!isVerified && (
                <form onSubmit={submit} className="flex flex-col gap-6 border border-rule p-6 bg-surface">
                    <div className="border-b border-rule pb-3">
                        <h3 className="font-semibold text-ink text-base">1. Immatriculation & Forme Sociale OHADA</h3>
                        <p className="text-xs text-ink-muted">Informations portées au registre de commerce.</p>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <Field error={!!errors.company_name}>
                            <Label>Dénomination sociale *</Label>
                            <Input
                                value={data.company_name}
                                onChange={(e) => setData('company_name', e.target.value)}
                            />
                            <FieldError>{errors.company_name}</FieldError>
                        </Field>

                        <Field error={!!errors.legal_form}>
                            <Label>Forme juridique OHADA *</Label>
                            <select
                                value={data.legal_form}
                                onChange={(e) => setData('legal_form', e.target.value)}
                                className="w-full border border-rule bg-surface p-2 text-sm text-ink"
                            >
                                <option value="sarl">SARL (Pluripersonnelle)</option>
                                <option value="sarlu">SARLU (Unipersonnelle)</option>
                                <option value="sas">SAS (Actions Simplifiée)</option>
                                <option value="sasu">SASU (Unipersonnelle)</option>
                                <option value="sa">SA (Société Anonyme)</option>
                            </select>
                            <FieldError>{errors.legal_form}</FieldError>
                        </Field>

                        <Field error={!!errors.rccm_number}>
                            <Label>Numéro RCCM *</Label>
                            <Input
                                value={data.rccm_number}
                                onChange={(e) => setData('rccm_number', e.target.value)}
                                placeholder="CD/KIN/RCCM/..."
                            />
                            <FieldError>{errors.rccm_number}</FieldError>
                        </Field>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <Field error={!!errors.tax_identification_number}>
                            <Label>Numéro d'Identification Fiscale (NIF) *</Label>
                            <Input
                                value={data.tax_identification_number}
                                onChange={(e) => setData('tax_identification_number', e.target.value)}
                                placeholder="A0000000X..."
                            />
                            <FieldError>{errors.tax_identification_number}</FieldError>
                        </Field>

                        <Field error={!!errors.national_identification_number}>
                            <Label>Identification Nationale (Id. Nat.)</Label>
                            <Input
                                value={data.national_identification_number}
                                onChange={(e) => setData('national_identification_number', e.target.value)}
                            />
                            <FieldError>{errors.national_identification_number}</FieldError>
                        </Field>

                        <Field error={!!errors.date_of_incorporation}>
                            <Label>Date de constitution *</Label>
                            <Input
                                type="date"
                                value={data.date_of_incorporation}
                                onChange={(e) => setData('date_of_incorporation', e.target.value)}
                            />
                            <FieldError>{errors.date_of_incorporation}</FieldError>
                        </Field>
                    </div>

                    <Field error={!!errors.registered_office_address}>
                        <Label>Adresse du siège social *</Label>
                        <Input
                            value={data.registered_office_address}
                            onChange={(e) => setData('registered_office_address', e.target.value)}
                            placeholder="Avenue, Quartier, Commune, Ville (RDC)"
                        />
                        <FieldError>{errors.registered_office_address}</FieldError>
                    </Field>

                    <Field error={!!errors.corporate_purpose}>
                        <Label>Objet social (selon statuts) *</Label>
                        <textarea
                            value={data.corporate_purpose}
                            onChange={(e) => setData('corporate_purpose', e.target.value)}
                            rows={3}
                            className="w-full border border-rule bg-surface p-2 text-sm text-ink"
                            placeholder="Description précise des activités statutaires..."
                        />
                        <FieldError>{errors.corporate_purpose}</FieldError>
                    </Field>

                    {/* Capital & Sector */}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <Field error={!!errors.share_capital_minor}>
                            <Label>Capital social (en cents / sous-unités) *</Label>
                            <Input
                                type="number"
                                value={data.share_capital_minor}
                                onChange={(e) => setData('share_capital_minor', parseInt(e.target.value) || 0)}
                            />
                            <FieldError>{errors.share_capital_minor}</FieldError>
                        </Field>

                        <Field error={!!errors.share_capital_currency}>
                            <Label>Devise du capital *</Label>
                            <Input
                                value={data.share_capital_currency}
                                onChange={(e) => setData('share_capital_currency', e.target.value.toUpperCase())}
                                maxLength={3}
                            />
                            <FieldError>{errors.share_capital_currency}</FieldError>
                        </Field>

                        <Field error={!!errors.industry_sector}>
                            <Label>Secteur économique *</Label>
                            <Input
                                value={data.industry_sector}
                                onChange={(e) => setData('industry_sector', e.target.value)}
                                placeholder="Technologies, Mines, Agro-business..."
                            />
                            <FieldError>{errors.industry_sector}</FieldError>
                        </Field>
                    </div>

                    {/* Legal Representative */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <Field error={!!errors.legal_representative_name}>
                            <Label>Nom du Représentant Légal *</Label>
                            <Input
                                value={data.legal_representative_name}
                                onChange={(e) => setData('legal_representative_name', e.target.value)}
                            />
                            <FieldError>{errors.legal_representative_name}</FieldError>
                        </Field>

                        <Field error={!!errors.legal_representative_title}>
                            <Label>Qualité / Titre du Représentant *</Label>
                            <Input
                                value={data.legal_representative_title}
                                onChange={(e) => setData('legal_representative_title', e.target.value)}
                                placeholder="Gérant, Directeur Général, Président..."
                            />
                            <FieldError>{errors.legal_representative_title}</FieldError>
                        </Field>
                    </div>

                    {/* Legal Documents Upload */}
                    <div className="border-t border-rule pt-4">
                        <h3 className="font-semibold text-ink text-base">2. Documents Légaux Obligatoires</h3>
                        <p className="text-xs text-ink-muted mb-4">Téléversez les actes authentiques et certifiés.</p>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <Field error={!!errors.rccm_file}>
                                <Label>Extrait RCCM (PDF)</Label>
                                <Input
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    onChange={(e) => setData('rccm_file', e.target.files?.[0] ?? null)}
                                />
                                <FieldError>{errors.rccm_file}</FieldError>
                            </Field>

                            <Field error={!!errors.statutes_file}>
                                <Label>Statuts certifiés (PDF)</Label>
                                <Input
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    onChange={(e) => setData('statutes_file', e.target.files?.[0] ?? null)}
                                />
                                <FieldError>{errors.statutes_file}</FieldError>
                            </Field>

                            <Field error={!!errors.nif_file}>
                                <Label>Attestation NIF (PDF)</Label>
                                <Input
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    onChange={(e) => setData('nif_file', e.target.files?.[0] ?? null)}
                                />
                                <FieldError>{errors.nif_file}</FieldError>
                            </Field>
                        </div>
                    </div>

                    <Button
                        type="submit"
                        disabled={processing}
                        className="self-start px-8 py-3"
                    >
                        {processing ? 'Enregistrement...' : 'Soumettre le dossier KYB'}
                    </Button>
                </form>
            )}

            {/* Document list */}
            {documents.length > 0 && (
                <div className="mt-8 border border-rule bg-surface p-4">
                    <h3 className="font-semibold text-sm mb-3">Documents légaux rattachés ({documents.length})</h3>
                    <div className="space-y-2">
                        {documents.map((doc) => (
                            <div key={doc.id} className="flex items-center justify-between border-b border-rule pb-2 last:border-b-0 text-sm">
                                <span className="flex items-center gap-2">
                                    <RiFileTextLine size={16} className="text-ink-muted" />
                                    {doc.title} ({doc.typeLabel})
                                </span>
                                <span className={`text-xs px-2 py-0.5 border ${doc.isVerified ? 'text-action border-action/30' : 'text-ink-muted border-rule'}`}>
                                    {doc.isVerified ? 'Vérifié' : 'En attente'}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}
