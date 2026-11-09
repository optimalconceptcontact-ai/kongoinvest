import { Head, useForm } from '@inertiajs/react';
import {
    RiCheckboxCircleLine,
    RiErrorWarningLine,
    RiFileTextLine,
    RiFingerprintLine,
    RiShieldCheckLine,
    RiUploadCloud2Line,
    RiUserLine,
} from '@remixicon/react';
import { useState, type FormEvent } from 'react';
import { BackLink } from '@/components/ui/BackLink';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/Field';
import { FieldError } from '@/components/ui/FieldError';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/Label';

type Props = {
    status: string;
    rejectionReason: string | null;
    submittedAt: string | null;
    documents: { id: number; name: string; downloadUrl: string }[];
    profile?: {
        id: number;
        status: string;
        statusLabel: string;
        firstName: string | null;
        lastName: string | null;
        postnom: string | null;
        dateOfBirth: string | null;
        placeOfBirth: string | null;
        nationalities: string[];
        residentialAddress: string | null;
        residentialCountry: string;
        phone: string | null;
        email: string | null;
        profession: string | null;
        employerOrActivity: string | null;
        isPep: boolean;
        pepFunction: string | null;
        isActingForThirdParty: boolean;
        rejectionReason: string | null;
        submittedAt: string | null;
        validUntil: string | null;
    };
    verifiedDocuments?: {
        id: number;
        type: string;
        typeLabel: string;
        issuingCountry: string;
        status: string;
        issuedAt: string | null;
        expiresAt: string | null;
        isExpired: boolean;
        downloadUrl?: string;
        check: {
            isReadable: boolean;
            ocrScore: number;
            isDuplicate: boolean;
            inconsistencies: string[];
        } | null;
    }[];
    biometrics?: {
        livenessPassed: boolean;
        livenessScore: number;
        faceMatchPassed: boolean;
        faceMatchScore: number;
    } | null;
};

const statusStyles: Record<string, string> = {
    draft: 'bg-surface-2 text-ink-muted border border-rule',
    submitted: 'bg-surface-2 text-ink border border-rule',
    under_review: 'bg-surface-2 text-ink border border-rule',
    approved: 'bg-surface-2 text-ink border border-rule',
    rejected: 'bg-signal/20 text-ink border border-signal/30',
    expired: 'bg-surface-2 text-ink-muted border border-rule',
};

const statusLabels: Record<string, string> = {
    draft: 'Brouillon',
    submitted: 'Soumis pour vérification',
    under_review: 'En cours d\'examen',
    approved: 'Identité Vérifiée & Conforme',
    rejected: 'Dossier Rejeté',
    expired: 'Validité Expirée',
};

export default function Kyc({
    status,
    rejectionReason,
    submittedAt,
    documents,
    profile,
    verifiedDocuments = [],
    biometrics,
}: Props) {
    const [activeTab, setActiveTab] = useState<'individual' | 'quick'>('individual');

    // Quick legacy upload form
    const legacyForm = useForm<{ files: File[] }>({ files: [] });

    // Comprehensive Individual KYC Form
    const kycForm = useForm<{
        first_name: string;
        last_name: string;
        postnom: string;
        date_of_birth: string;
        place_of_birth: string;
        nationalities: string[];
        residential_address: string;
        residential_country: string;
        phone: string;
        email: string;
        profession: string;
        employer_or_activity: string;
        is_pep: boolean;
        pep_function: string;
        pep_start_date: string;
        pep_end_date: string;
        is_acting_for_third_party: boolean;
        third_party_details: string;
        document_type: string;
        issuing_country: string;
        document_number: string;
        issued_at: string;
        expires_at: string;
        front_file: File | null;
        back_file: File | null;
        selfie_file: File | null;
    }>({
        first_name: profile?.firstName ?? '',
        last_name: profile?.lastName ?? '',
        postnom: profile?.postnom ?? '',
        date_of_birth: profile?.dateOfBirth ?? '',
        place_of_birth: profile?.placeOfBirth ?? '',
        nationalities: profile?.nationalities ?? ['CD'],
        residential_address: profile?.residentialAddress ?? '',
        residential_country: profile?.residentialCountry ?? 'CD',
        phone: profile?.phone ?? '',
        email: profile?.email ?? '',
        profession: profile?.profession ?? '',
        employer_or_activity: profile?.employerOrActivity ?? '',
        is_pep: profile?.isPep ?? false,
        pep_function: profile?.pepFunction ?? '',
        pep_start_date: '',
        pep_end_date: '',
        is_acting_for_third_party: profile?.isActingForThirdParty ?? false,
        third_party_details: '',
        document_type: 'drc_voter_card',
        issuing_country: 'CD',
        document_number: '',
        issued_at: '',
        expires_at: '',
        front_file: null,
        back_file: null,
        selfie_file: null,
    });

    function submitLegacy(e: FormEvent) {
        e.preventDefault();
        legacyForm.post('/settings/kyc', { forceFormData: true });
    }

    function submitFullKyc(e: FormEvent) {
        e.preventDefault();
        kycForm.post('/settings/kyc/individual', { forceFormData: true });
    }

    const canSubmit = status === 'draft' || status === 'rejected';

    return (
        <div className="mx-auto max-w-3xl px-6 py-12">
            <Head title="Vérification d'identité (KYC)" />
            <BackLink href="/dashboard" />
            <p className="text-eyebrow">Conformité & Sécurité</p>
            <h1 className="mt-2 mb-2 text-page-title">
                Vérification d'identité (KYC)
            </h1>
            <p className="mb-6 text-body">
                Vos documents et données personnelles sont strictement chiffrés, protégés conformément à la législation
                congolaise (Loi n° 22/068 et Loi n° 25/048) et conservés sur un espace sécurisé dédié.
            </p>

            {/* Current Status Badge */}
            <div className={`mb-6 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium ${statusStyles[status]}`}>
                <RiShieldCheckLine size={16} /> {statusLabels[status] ?? status}
            </div>

            {status === 'rejected' && rejectionReason && (
                <div className="mb-6 border border-rule-strong bg-surface-2 px-4 py-3 text-lg leading-5 text-ink-muted">
                    <strong className="text-ink">Motif du rejet :</strong> {rejectionReason}
                </div>
            )}

            {/* Verified Profile Card if approved */}
            {profile?.status === 'approved' && (
                <div className="mb-8 border border-rule bg-surface p-6 shadow-xs">
                    <div className="flex items-center gap-3 text-action mb-4">
                        <RiCheckboxCircleLine size={24} />
                        <h2 className="text-lg font-bold text-ink">Dossier d'identité validé</h2>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-ink-muted">
                        <div>
                            <span className="font-semibold text-ink">Nom complet :</span> {profile.firstName} {profile.lastName} {profile.postnom}
                        </div>
                        <div>
                            <span className="font-semibold text-ink">Nationalité :</span> {profile.nationalities.join(', ')}
                        </div>
                        <div>
                            <span className="font-semibold text-ink">Résidence :</span> {profile.residentialAddress}, {profile.residentialCountry}
                        </div>
                        <div>
                            <span className="font-semibold text-ink">Profession :</span> {profile.profession}
                        </div>
                    </div>
                    {profile.validUntil && (
                        <p className="mt-4 text-xs text-ink-muted">
                            Valable jusqu'au {new Date(profile.validUntil).toLocaleDateString('fr-FR')} (Réutilisable sur vos opérations).
                        </p>
                    )}
                </div>
            )}

            {/* Existing Documents list */}
            {(documents.length > 0 || verifiedDocuments.length > 0) && (
                <div className="mb-8 border border-rule">
                    <div className="border-b border-rule bg-surface-2 p-3 font-semibold text-sm">
                        Documents justificatifs enregistrés
                    </div>
                    {verifiedDocuments.map((doc) => (
                        <div key={doc.id} className="flex items-center justify-between border-b border-rule p-3 text-sm last:border-b-0">
                            <div>
                                <span className="font-medium text-ink">{doc.typeLabel} ({doc.issuingCountry})</span>
                                {doc.isExpired && <span className="ml-2 text-xs text-signal font-semibold">Expiré</span>}
                                {doc.check && (
                                    <div className="text-xs text-ink-muted mt-0.5">
                                        Indice de lisibilité OCR : {(doc.check.ocrScore * 100).toFixed(0)} %
                                        {doc.check.isDuplicate && <span className="text-signal ml-2">Doublon détecté</span>}
                                    </div>
                                )}
                            </div>
                            <span className="text-xs uppercase bg-surface-2 px-2 py-1 border border-rule text-ink-muted">
                                {doc.status}
                            </span>
                            {doc.downloadUrl && (
                                <a
                                    href={doc.downloadUrl}
                                    className="ml-3 text-xs text-action underline decoration-1 underline-offset-4 hover:decoration-2"
                                >
                                    Télécharger
                                </a>
                            )}
                        </div>
                    ))}
                    {documents.map((d) => (
                        <div key={d.id} className="flex items-center justify-between border-b border-rule p-3 text-sm last:border-b-0">
                            <span className="flex items-center gap-2">
                                <RiFileTextLine size={16} className="text-ink-muted" /> {d.name}
                            </span>
                            <a
                                href={d.downloadUrl}
                                className="font-mono text-sm text-action underline decoration-1 underline-offset-4 hover:decoration-2"
                            >
                                Télécharger
                            </a>
                        </div>
                    ))}
                </div>
            )}

            {/* Form Section if can submit */}
            {canSubmit && (
                <div>
                    {/* Navigation Tabs */}
                    <div className="flex border-b border-rule mb-6 gap-4">
                        <button
                            type="button"
                            onClick={() => setActiveTab('individual')}
                            className={`pb-3 text-sm font-medium border-b-2 transition-colors ${
                                activeTab === 'individual'
                                    ? 'border-action text-action'
                                    : 'border-transparent text-ink-muted hover:text-ink'
                            }`}
                        >
                            <span className="flex items-center gap-2">
                                <RiUserLine size={16} /> Parcours KYC Complet (RDC)
                            </span>
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab('quick')}
                            className={`pb-3 text-sm font-medium border-b-2 transition-colors ${
                                activeTab === 'quick'
                                    ? 'border-action text-action'
                                    : 'border-transparent text-ink-muted hover:text-ink'
                            }`}
                        >
                            <span className="flex items-center gap-2">
                                <RiUploadCloud2Line size={16} /> Dépôt Rapide de Documents
                            </span>
                        </button>
                    </div>

                    {activeTab === 'individual' ? (
                        <form onSubmit={submitFullKyc} className="flex flex-col gap-6 border border-rule p-6 bg-surface">
                            <div className="border-b border-rule pb-3">
                                <h3 className="font-semibold text-ink text-base">1. État Civil & Résidence</h3>
                                <p className="text-xs text-ink-muted">Conformité état-civil et identification nationale en RDC.</p>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <Field error={!!kycForm.errors.first_name}>
                                    <Label>Prénom *</Label>
                                    <Input
                                        value={kycForm.data.first_name}
                                        onChange={(e) => kycForm.setData('first_name', e.target.value)}
                                        placeholder="Ex: Jean"
                                    />
                                    <FieldError>{kycForm.errors.first_name}</FieldError>
                                </Field>

                                <Field error={!!kycForm.errors.last_name}>
                                    <Label>Nom de famille *</Label>
                                    <Input
                                        value={kycForm.data.last_name}
                                        onChange={(e) => kycForm.setData('last_name', e.target.value)}
                                        placeholder="Ex: Ilunga"
                                    />
                                    <FieldError>{kycForm.errors.last_name}</FieldError>
                                </Field>

                                <Field error={!!kycForm.errors.postnom}>
                                    <Label>Postnom (RDC)</Label>
                                    <Input
                                        value={kycForm.data.postnom}
                                        onChange={(e) => kycForm.setData('postnom', e.target.value)}
                                        placeholder="Ex: Mwamba"
                                    />
                                    <FieldError>{kycForm.errors.postnom}</FieldError>
                                </Field>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <Field error={!!kycForm.errors.date_of_birth}>
                                    <Label>Date de naissance *</Label>
                                    <Input
                                        type="date"
                                        value={kycForm.data.date_of_birth}
                                        onChange={(e) => kycForm.setData('date_of_birth', e.target.value)}
                                    />
                                    <FieldError>{kycForm.errors.date_of_birth}</FieldError>
                                </Field>

                                <Field error={!!kycForm.errors.place_of_birth}>
                                    <Label>Lieu de naissance *</Label>
                                    <Input
                                        value={kycForm.data.place_of_birth}
                                        onChange={(e) => kycForm.setData('place_of_birth', e.target.value)}
                                        placeholder="Ex: Kinshasa, Lubumbashi..."
                                    />
                                    <FieldError>{kycForm.errors.place_of_birth}</FieldError>
                                </Field>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <Field error={!!kycForm.errors.residential_address}>
                                    <Label>Adresse de résidence *</Label>
                                    <Input
                                        value={kycForm.data.residential_address}
                                        onChange={(e) => kycForm.setData('residential_address', e.target.value)}
                                        placeholder="Numéro, Avenue, Quartier, Commune..."
                                    />
                                    <FieldError>{kycForm.errors.residential_address}</FieldError>
                                </Field>

                                <Field error={!!kycForm.errors.residential_country}>
                                    <Label>Pays de résidence *</Label>
                                    <Input
                                        value={kycForm.data.residential_country}
                                        onChange={(e) => kycForm.setData('residential_country', e.target.value.toUpperCase())}
                                        maxLength={2}
                                        placeholder="Code ISO 2 lettres (ex: CD, BE, FR)"
                                    />
                                    <FieldError>{kycForm.errors.residential_country}</FieldError>
                                </Field>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <Field error={!!kycForm.errors.profession}>
                                    <Label>Profession *</Label>
                                    <Input
                                        value={kycForm.data.profession}
                                        onChange={(e) => kycForm.setData('profession', e.target.value)}
                                        placeholder="Ex: Ingénieur, Entrepreneur, Cadre..."
                                    />
                                    <FieldError>{kycForm.errors.profession}</FieldError>
                                </Field>

                                <Field error={!!kycForm.errors.employer_or_activity}>
                                    <Label>Employeur ou activité économique</Label>
                                    <Input
                                        value={kycForm.data.employer_or_activity}
                                        onChange={(e) => kycForm.setData('employer_or_activity', e.target.value)}
                                        placeholder="Entreprise ou secteur d'exercice"
                                    />
                                    <FieldError>{kycForm.errors.employer_or_activity}</FieldError>
                                </Field>
                            </div>

                            {/* Section 2: PEP & Mandatory Representation Declaration */}
                            <div className="border-t border-rule pt-4">
                                <h3 className="font-semibold text-ink text-base">2. Statut PEP & Déclarations</h3>
                                <p className="text-xs text-ink-muted mb-4">Conformité aux exigences de vigilance renforcée.</p>

                                <div className="space-y-4">
                                    <label className="flex items-start gap-3 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={kycForm.data.is_pep}
                                            onChange={(e) => kycForm.setData('is_pep', e.target.checked)}
                                            className="mt-1"
                                        />
                                        <span className="text-sm text-ink">
                                            <strong>Personne Politiquement Exposée (PEP) :</strong> Vous ou un membre de votre famille proche occupez ou avez occupé une fonction publique, politique ou militaire de premier plan.
                                        </span>
                                    </label>

                                    {kycForm.data.is_pep && (
                                        <Field error={!!kycForm.errors.pep_function}>
                                            <Label>Fonction publique ou mandat exercé *</Label>
                                            <Input
                                                value={kycForm.data.pep_function}
                                                onChange={(e) => kycForm.setData('pep_function', e.target.value)}
                                                placeholder="Intitulé de la fonction, institution et pays"
                                            />
                                            <FieldError>{kycForm.errors.pep_function}</FieldError>
                                        </Field>
                                    )}

                                    <label className="flex items-start gap-3 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={kycForm.data.is_acting_for_third_party}
                                            onChange={(e) => kycForm.setData('is_acting_for_third_party', e.target.checked)}
                                            className="mt-1"
                                        />
                                        <span className="text-sm text-ink">
                                            Agissez-vous pour le compte d'un tiers ou d'un ayant droit économique (bénéficiaire effectif) ?
                                        </span>
                                    </label>
                                </div>
                            </div>

                            {/* Section 3: Identity Document */}
                            <div className="border-t border-rule pt-4">
                                <h3 className="font-semibold text-ink text-base">3. Pièce d'Identité Officielle</h3>
                                <p className="text-xs text-ink-muted mb-4">Téléversez un document officiel en cours de validité.</p>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                    <Field error={!!kycForm.errors.document_type}>
                                        <Label>Type de pièce *</Label>
                                        <select
                                            value={kycForm.data.document_type}
                                            onChange={(e) => kycForm.setData('document_type', e.target.value)}
                                            className="w-full border border-rule bg-surface p-2 text-sm text-ink"
                                        >
                                            <option value="drc_voter_card">Carte d'électeur RDC (CENI)</option>
                                            <option value="passport">Passeport biométrique</option>
                                            <option value="national_id">Carte nationale d'identité (CNI / ONIP)</option>
                                            <option value="driving_license">Permis de conduire</option>
                                            <option value="other_official_id">Autre document officiel</option>
                                        </select>
                                        <FieldError>{kycForm.errors.document_type}</FieldError>
                                    </Field>

                                    <Field error={!!kycForm.errors.document_number}>
                                        <Label>Numéro de la pièce *</Label>
                                        <Input
                                            value={kycForm.data.document_number}
                                            onChange={(e) => kycForm.setData('document_number', e.target.value)}
                                            placeholder="Numéro tel qu'inscrit sur la pièce"
                                        />
                                        <FieldError>{kycForm.errors.document_number}</FieldError>
                                    </Field>
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                    <Field error={!!kycForm.errors.front_file}>
                                        <Label>Document Recto (PDF, JPG, PNG — 10 Mo max) *</Label>
                                        <Input
                                            type="file"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            onChange={(e) => kycForm.setData('front_file', e.target.files?.[0] ?? null)}
                                        />
                                        <FieldError>{kycForm.errors.front_file}</FieldError>
                                    </Field>

                                    <Field error={!!kycForm.errors.back_file}>
                                        <Label>Document Verso (le cas échéant)</Label>
                                        <Input
                                            type="file"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            onChange={(e) => kycForm.setData('back_file', e.target.files?.[0] ?? null)}
                                        />
                                        <FieldError>{kycForm.errors.back_file}</FieldError>
                                    </Field>
                                </div>
                            </div>

                            {/* Section 4: Biometrics Selfie */}
                            <div className="border-t border-rule pt-4">
                                <h3 className="font-semibold text-ink text-base">4. Vérification Biométrique (Optionnel mais recommandé)</h3>
                                <p className="text-xs text-ink-muted mb-4">Selfie de contrôle pour valider la présence réelle et accélérer la vérification.</p>

                                <Field error={!!kycForm.errors.selfie_file}>
                                    <Label>Photo Selfie claire (JPG, PNG)</Label>
                                    <Input
                                        type="file"
                                        accept=".jpg,.jpeg,.png"
                                        onChange={(e) => kycForm.setData('selfie_file', e.target.files?.[0] ?? null)}
                                    />
                                    <FieldError>{kycForm.errors.selfie_file}</FieldError>
                                </Field>
                            </div>

                            <Button
                                type="submit"
                                disabled={kycForm.processing}
                                className="self-start px-8 py-3 mt-4"
                            >
                                {kycForm.processing ? 'Traitement & Soumission...' : 'Soumettre le dossier KYC complet'}
                            </Button>
                        </form>
                    ) : (
                        /* Legacy Quick Upload Form */
                        <form
                            onSubmit={submitLegacy}
                            className="flex flex-col gap-4 border border-rule p-5 bg-surface"
                        >
                            <Field error={!!legacyForm.errors.files}>
                                <Label>
                                    Pièces justificatives au choix (PDF, JPG, PNG — 10 Mo max chacun)
                                </Label>
                                <Input
                                    type="file"
                                    multiple
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    onChange={(e) =>
                                        legacyForm.setData('files', Array.from(e.target.files ?? []))
                                    }
                                    error={!!legacyForm.errors.files}
                                />
                                <FieldError>{legacyForm.errors.files}</FieldError>
                            </Field>
                            <Button
                                type="submit"
                                disabled={legacyForm.processing || legacyForm.data.files.length === 0}
                                className="self-start px-6 py-3"
                            >
                                {legacyForm.processing ? 'Envoi...' : 'Soumettre les documents'}
                            </Button>
                        </form>
                    )}
                </div>
            )}
        </div>
    );
}
