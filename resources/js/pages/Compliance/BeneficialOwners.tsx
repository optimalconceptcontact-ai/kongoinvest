import { Head, useForm } from '@inertiajs/react';
import {
    RiAddLine,
    RiArrowRightLine,
    RiCommunityLine,
    RiNodeTree,
    RiShieldCheckLine,
    RiShieldUserLine,
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
    verification: {
        id: number;
        companyName: string;
        status: string;
    };
    beneficialOwners: {
        id: number;
        fullName: string;
        nationality: string;
        residence: string;
        directPercentage: number;
        indirectPercentage: number;
        totalPercentage: number;
        votingPercentage: number;
        controlNature: string;
        controlNatureLabel: string;
        isPep: boolean;
        pepDetails: string | null;
        meetsThreshold: boolean;
        isVerified: boolean;
    }[];
    ownershipGraph: {
        nodes: {
            id: string;
            label: string;
            type: string;
            percentage: number;
            is_pep: boolean;
            tier: number;
        }[];
        edges: {
            from: string;
            to: string;
            label: string;
            equity_pct: number;
            voting_pct: number;
        }[];
    };
};

export default function BeneficialOwners({ verification, beneficialOwners, ownershipGraph }: Props) {
    const [showUboModal, setShowUboModal] = useState(false);
    const [showLinkModal, setShowLinkModal] = useState(false);

    // Form for declaring individual UBO
    const uboForm = useForm<{
        first_name: string;
        last_name: string;
        postnom: string;
        date_of_birth: string;
        nationality: string;
        country_of_residence: string;
        residential_address: string;
        is_pep: boolean;
        pep_details: string;
        direct_ownership_bps: number;
        indirect_ownership_bps: number;
        voting_rights_bps: number;
        control_nature: string;
        justification_notes: string;
    }>({
        first_name: '',
        last_name: '',
        postnom: '',
        date_of_birth: '',
        nationality: 'CD',
        country_of_residence: 'CD',
        residential_address: '',
        is_pep: false,
        pep_details: '',
        direct_ownership_bps: 2500, // 25.00%
        indirect_ownership_bps: 0,
        voting_rights_bps: 2500,
        control_nature: 'direct_equity',
        justification_notes: '',
    });

    // Form for multi-tier holding link
    const linkForm = useForm<{
        parent_entity_type: string;
        parent_name: string;
        parent_registration_number: string;
        parent_country: string;
        child_entity_name: string;
        child_registration_number: string;
        tier_level: number;
        equity_percentage_bps: number;
        voting_percentage_bps: number;
        control_mechanism: string;
    }>({
        parent_entity_type: 'individual',
        parent_name: '',
        parent_registration_number: '',
        parent_country: 'CD',
        child_entity_name: verification.companyName,
        child_registration_number: '',
        tier_level: 1,
        equity_percentage_bps: 5000,
        voting_percentage_bps: 5000,
        control_mechanism: 'direct_equity',
    });

    function submitUbo(e: FormEvent) {
        e.preventDefault();
        uboForm.post(`/verifications/${verification.id}/beneficial-owners`, {
            onSuccess: () => {
                setShowUboModal(false);
                uboForm.reset();
            },
        });
    }

    function submitLink(e: FormEvent) {
        e.preventDefault();
        linkForm.post(`/verifications/${verification.id}/relationships`, {
            onSuccess: () => {
                setShowLinkModal(false);
                linkForm.reset();
            },
        });
    }

    return (
        <div className="mx-auto max-w-5xl px-6 py-12">
            <Head title={`Bénéficiaires Effectifs (UBO) — ${verification.companyName}`} />
            <BackLink href={`/companies/${verification.id}/kyb`} />
            <p className="text-eyebrow">Conformité UBO & AML</p>
            <h1 className="mt-2 mb-2 text-page-title flex items-center gap-3">
                <RiNodeTree size={28} className="text-action" />
                Bénéficiaires Effectifs & Chaîne de Détention (UBO)
            </h1>
            <p className="mb-6 text-body">
                Cartographie des personnes physiques exerçant le contrôle effectif direct ou indirect (seuil légal standard de 25 % du capital ou des droits de vote, ou contrôle de fait selon le droit OHADA et la Loi 22/068).
            </p>

            {/* Action Bar */}
            <div className="flex flex-wrap items-center justify-between gap-4 mb-8">
                <div className="flex items-center gap-2">
                    <span className="text-xs uppercase px-3 py-1 bg-surface-2 border border-rule font-medium">
                        Société cible : {verification.companyName}
                    </span>
                    <span className="text-xs uppercase px-3 py-1 bg-surface-2 border border-rule font-medium">
                        UBO déclarés : {beneficialOwners.length}
                    </span>
                </div>
                <div className="flex gap-3">
                    <Button onClick={() => setShowUboModal(true)} size="sm">
                        <RiAddLine size={16} /> Déclarer une personne UBO
                    </Button>
                    <Button onClick={() => setShowLinkModal(true)} variant="outline" size="sm">
                        <RiNodeTree size={16} /> Ajouter un échelon de détention
                    </Button>
                </div>
            </div>

            {/* Graph Visualization Card */}
            <div className="mb-10 border border-rule bg-surface p-6 shadow-xs">
                <h2 className="text-base font-bold text-ink mb-2 flex items-center gap-2">
                    <RiNodeTree size={20} className="text-action" />
                    Organigramme de détention & Chaîne de contrôle
                </h2>
                <p className="text-xs text-ink-muted mb-6">
                    Représentation des relations entre détenteurs ultimes, holdings intermédiaires et société cible.
                </p>

                {ownershipGraph.edges.length === 0 && beneficialOwners.length === 0 ? (
                    <div className="p-8 text-center text-ink-muted border border-dashed border-rule bg-surface-2 text-sm">
                        Aucun bénéficiaire effectif ni lien de détention enregistré pour le moment.
                    </div>
                ) : (
                    <div className="space-y-4">
                        {ownershipGraph.edges.map((edge, idx) => (
                            <div key={idx} className="flex items-center gap-4 p-4 border border-rule bg-surface-2 text-sm">
                                <div className="font-semibold text-ink flex items-center gap-2">
                                    <RiUserLine size={18} className="text-action" />
                                    {edge.from.replace('parent-', '').replace('ubo-', 'UBO ')}
                                </div>
                                <RiArrowRightLine size={18} className="text-ink-muted" />
                                <div className="bg-surface px-3 py-1 border border-rule text-xs font-mono font-bold text-action">
                                    Détention : {edge.label} (Votes : {edge.voting_pct} %)
                                </div>
                                <RiArrowRightLine size={18} className="text-ink-muted" />
                                <div className="font-semibold text-ink flex items-center gap-2">
                                    <RiCommunityLine size={18} className="text-action" />
                                    {edge.to === `target-${verification.id}` ? verification.companyName : edge.to}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            {/* Table of Beneficial Owners */}
            <div className="border border-rule bg-surface">
                <div className="p-4 border-b border-rule bg-surface-2 font-semibold text-sm flex items-center justify-between">
                    <span>Personnes Physiques Bénéficiaires Effectifs (UBO)</span>
                    <span className="text-xs text-ink-muted">Seuil statutory de référence : 25 %</span>
                </div>
                {beneficialOwners.length === 0 ? (
                    <div className="p-6 text-center text-sm text-ink-muted">
                        Aucun UBO n'a encore été déclaré pour cette structure.
                    </div>
                ) : (
                    <div className="divide-y divide-rule text-sm">
                        {beneficialOwners.map((ubo) => (
                            <div key={ubo.id} className="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div>
                                    <div className="font-bold text-ink flex items-center gap-2">
                                        <RiShieldUserLine size={18} className="text-action" />
                                        {ubo.fullName} ({ubo.nationality})
                                        {ubo.isPep && (
                                            <span className="ml-2 px-2 py-0.5 text-xs bg-signal/20 text-ink font-mono uppercase">
                                                PEP ({ubo.pepDetails || 'Exposée'})
                                            </span>
                                        )}
                                    </div>
                                    <div className="text-xs text-ink-muted mt-1">
                                        Résidence : {ubo.residence} | Nature du contrôle : {ubo.controlNatureLabel}
                                    </div>
                                </div>

                                <div className="flex items-center gap-4">
                                    <div className="text-right">
                                        <div className="font-mono font-bold text-ink text-base">
                                            {ubo.totalPercentage} %
                                        </div>
                                        <div className="text-xs text-ink-muted">
                                            Direct : {ubo.directPercentage}% | Indirect : {ubo.indirectPercentage}%
                                        </div>
                                    </div>

                                    <span className={`text-xs px-2.5 py-1 border font-medium ${
                                        ubo.meetsThreshold
                                            ? 'bg-action/10 text-action border-action/30'
                                            : 'bg-surface-2 text-ink-muted border-rule'
                                    }`}>
                                        {ubo.meetsThreshold ? 'UBO Qualifié (>= 25 %)' : 'Associé Minoritaire'}
                                    </span>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            {/* Modal: Declare UBO */}
            {showUboModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-2xl border border-rule bg-surface p-6 shadow-lg max-h-[90vh] overflow-y-auto">
                        <h3 className="text-lg font-bold text-ink mb-1">Déclarer un Bénéficiaire Effectif (UBO)</h3>
                        <p className="text-xs text-ink-muted mb-4">Personne physique détenant directement ou indirectement le contrôle.</p>

                        <form onSubmit={submitUbo} className="space-y-4">
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <Field error={!!uboForm.errors.first_name}>
                                    <Label>Prénom *</Label>
                                    <Input
                                        value={uboForm.data.first_name}
                                        onChange={(e) => uboForm.setData('first_name', e.target.value)}
                                    />
                                    <FieldError>{uboForm.errors.first_name}</FieldError>
                                </Field>

                                <Field error={!!uboForm.errors.last_name}>
                                    <Label>Nom *</Label>
                                    <Input
                                        value={uboForm.data.last_name}
                                        onChange={(e) => uboForm.setData('last_name', e.target.value)}
                                    />
                                    <FieldError>{uboForm.errors.last_name}</FieldError>
                                </Field>

                                <Field error={!!uboForm.errors.postnom}>
                                    <Label>Postnom (RDC)</Label>
                                    <Input
                                        value={uboForm.data.postnom}
                                        onChange={(e) => uboForm.setData('postnom', e.target.value)}
                                    />
                                    <FieldError>{uboForm.errors.postnom}</FieldError>
                                </Field>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <Field error={!!uboForm.errors.nationality}>
                                    <Label>Nationalité *</Label>
                                    <Input
                                        value={uboForm.data.nationality}
                                        onChange={(e) => uboForm.setData('nationality', e.target.value.toUpperCase())}
                                        maxLength={2}
                                    />
                                    <FieldError>{uboForm.errors.nationality}</FieldError>
                                </Field>

                                <Field error={!!uboForm.errors.country_of_residence}>
                                    <Label>Pays de résidence *</Label>
                                    <Input
                                        value={uboForm.data.country_of_residence}
                                        onChange={(e) => uboForm.setData('country_of_residence', e.target.value.toUpperCase())}
                                        maxLength={2}
                                    />
                                    <FieldError>{uboForm.errors.country_of_residence}</FieldError>
                                </Field>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <Field error={!!uboForm.errors.direct_ownership_bps}>
                                    <Label>Détention directe (bps, ex: 2500 = 25%)</Label>
                                    <Input
                                        type="number"
                                        value={uboForm.data.direct_ownership_bps}
                                        onChange={(e) => uboForm.setData('direct_ownership_bps', parseInt(e.target.value) || 0)}
                                    />
                                    <FieldError>{uboForm.errors.direct_ownership_bps}</FieldError>
                                </Field>

                                <Field error={!!uboForm.errors.indirect_ownership_bps}>
                                    <Label>Détention indirecte (bps)</Label>
                                    <Input
                                        type="number"
                                        value={uboForm.data.indirect_ownership_bps}
                                        onChange={(e) => uboForm.setData('indirect_ownership_bps', parseInt(e.target.value) || 0)}
                                    />
                                    <FieldError>{uboForm.errors.indirect_ownership_bps}</FieldError>
                                </Field>

                                <Field error={!!uboForm.errors.voting_rights_bps}>
                                    <Label>Droits de vote (bps)</Label>
                                    <Input
                                        type="number"
                                        value={uboForm.data.voting_rights_bps}
                                        onChange={(e) => uboForm.setData('voting_rights_bps', parseInt(e.target.value) || 0)}
                                    />
                                    <FieldError>{uboForm.errors.voting_rights_bps}</FieldError>
                                </Field>
                            </div>

                            <Field error={!!uboForm.errors.control_nature}>
                                <Label>Nature du contrôle *</Label>
                                <select
                                    value={uboForm.data.control_nature}
                                    onChange={(e) => uboForm.setData('control_nature', e.target.value)}
                                    className="w-full border border-rule bg-surface p-2 text-sm text-ink"
                                >
                                    <option value="direct_equity">Détention directe de capital</option>
                                    <option value="indirect_equity">Détention indirecte via holding</option>
                                    <option value="voting_rights">Droits de vote prépondérants</option>
                                    <option value="legal_representation">Représentant légal effectif</option>
                                    <option value="shareholders_agreement">Pacte d'actionnaires / Accords statutaires</option>
                                    <option value="de_facto_control">Contrôle de fait</option>
                                </select>
                                <FieldError>{uboForm.errors.control_nature}</FieldError>
                            </Field>

                            <div className="border-t border-rule pt-3">
                                <label className="flex items-center gap-2 cursor-pointer mb-2">
                                    <input
                                        type="checkbox"
                                        checked={uboForm.data.is_pep}
                                        onChange={(e) => uboForm.setData('is_pep', e.target.checked)}
                                    />
                                    <span className="text-sm font-medium text-ink">Cette personne est-elle une PEP (Personne Politiquement Exposée) ?</span>
                                </label>
                                {uboForm.data.is_pep && (
                                    <Field error={!!uboForm.errors.pep_details}>
                                        <Label>Fonction publique ou mandat de la PEP</Label>
                                        <Input
                                            value={uboForm.data.pep_details}
                                            onChange={(e) => uboForm.setData('pep_details', e.target.value)}
                                        />
                                        <FieldError>{uboForm.errors.pep_details}</FieldError>
                                    </Field>
                                )}
                            </div>

                            <div className="flex justify-end gap-3 pt-4 border-t border-rule">
                                <Button type="button" variant="outline" onClick={() => setShowUboModal(false)}>
                                    Annuler
                                </Button>
                                <Button type="submit" disabled={uboForm.processing}>
                                    {uboForm.processing ? 'Enregistrement...' : 'Valider la déclaration UBO'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal: Add Link in Chain */}
            {showLinkModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-lg border border-rule bg-surface p-6 shadow-lg">
                        <h3 className="text-lg font-bold text-ink mb-1">Ajouter un lien de détention</h3>
                        <p className="text-xs text-ink-muted mb-4">Structure multi-niveaux (ex : Holding A détient X % de Société B).</p>

                        <form onSubmit={submitLink} className="space-y-4">
                            <Field error={!!linkForm.errors.parent_name}>
                                <Label>Nom de l'entité mère (Holding ou Personne) *</Label>
                                <Input
                                    value={linkForm.data.parent_name}
                                    onChange={(e) => linkForm.setData('parent_name', e.target.value)}
                                    placeholder="Ex: Holding Invest Congo SAS"
                                />
                                <FieldError>{linkForm.errors.parent_name}</FieldError>
                            </Field>

                            <Field error={!!linkForm.errors.child_entity_name}>
                                <Label>Nom de l'entité fille détenue *</Label>
                                <Input
                                    value={linkForm.data.child_entity_name}
                                    onChange={(e) => linkForm.setData('child_entity_name', e.target.value)}
                                />
                                <FieldError>{linkForm.errors.child_entity_name}</FieldError>
                            </Field>

                            <div className="grid grid-cols-2 gap-3">
                                <Field error={!!linkForm.errors.tier_level}>
                                    <Label>Niveau d'échelon (1 = direct, 2, 3...) *</Label>
                                    <Input
                                        type="number"
                                        min={1}
                                        max={5}
                                        value={linkForm.data.tier_level}
                                        onChange={(e) => linkForm.setData('tier_level', parseInt(e.target.value) || 1)}
                                    />
                                    <FieldError>{linkForm.errors.tier_level}</FieldError>
                                </Field>

                                <Field error={!!linkForm.errors.equity_percentage_bps}>
                                    <Label>Pourcentage de détention (bps) *</Label>
                                    <Input
                                        type="number"
                                        value={linkForm.data.equity_percentage_bps}
                                        onChange={(e) => linkForm.setData('equity_percentage_bps', parseInt(e.target.value) || 0)}
                                    />
                                    <FieldError>{linkForm.errors.equity_percentage_bps}</FieldError>
                                </Field>
                            </div>

                            <div className="flex justify-end gap-3 pt-4 border-t border-rule">
                                <Button type="button" variant="outline" onClick={() => setShowLinkModal(false)}>
                                    Annuler
                                </Button>
                                <Button type="submit" disabled={linkForm.processing}>
                                    {linkForm.processing ? 'Ajout...' : 'Ajouter le lien'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}
