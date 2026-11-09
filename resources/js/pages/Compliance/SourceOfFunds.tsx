import { Head, useForm } from '@inertiajs/react';
import {
    RiBankCardLine,
    RiFileTextLine,
    RiSafe2Line,
    RiShieldCheckLine,
} from '@remixicon/react';
import { useState, type FormEvent } from 'react';
import { BackLink } from '@/components/ui/BackLink';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/Field';
import { FieldError } from '@/components/ui/FieldError';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/Label';

type Props = {
    declarations: {
        id: number;
        amountFormatted: string;
        category: string;
        categoryLabel: string;
        origin: string;
        bank: string | null;
        isVerified: boolean;
        createdAt: string;
    }[];
    sourceOfWealth: {
        id: number;
        description: string;
        components: any;
        isVerified: boolean;
    } | null;
};

export default function SourceOfFunds({ declarations, sourceOfWealth }: Props) {
    const [activeTab, setActiveTab] = useState<'sof' | 'sow'>('sof');

    // SOF Form
    const sofForm = useForm<{
        amount_minor: number;
        currency: string;
        funds_category: string;
        origin_description: string;
        bank_name: string;
        bank_country: string;
        account_iban_or_number: string;
        operation_reference: string;
        supporting_documents: File[];
    }>({
        amount_minor: 1000000, // $10,000 USD
        currency: 'USD',
        funds_category: 'business_dividends',
        origin_description: '',
        bank_name: '',
        bank_country: 'CD',
        account_iban_or_number: '',
        operation_reference: '',
        supporting_documents: [],
    });

    // SOW Form
    const sowForm = useForm<{
        wealth_description: string;
        estimated_total_net_worth_minor: number;
        currency: string;
    }>({
        wealth_description: sourceOfWealth?.description ?? '',
        estimated_total_net_worth_minor: 5000000,
        currency: 'USD',
    });

    function submitSof(e: FormEvent) {
        e.preventDefault();
        sofForm.post('/source-of-funds', { forceFormData: true });
    }

    function submitSow(e: FormEvent) {
        e.preventDefault();
        sowForm.post('/source-of-wealth');
    }

    return (
        <div className="mx-auto max-w-4xl px-6 py-12">
            <Head title="Origine des Fonds & Patrimoine (SOF / SOW)" />
            <BackLink href="/dashboard" />
            <p className="text-eyebrow">Conformité Financière AML</p>
            <h1 className="mt-2 mb-2 text-page-title flex items-center gap-3">
                <RiSafe2Line size={28} className="text-action" />
                Justification des Fonds & du Patrimoine (SOF / SOW)
            </h1>
            <p className="mb-6 text-body">
                Distinction impérative entre la provenance des fonds injectés dans l'opération (Source of Funds)
                et la constitution globale du patrimoine de l'investisseur (Source of Wealth) selon les standards GAFI et la législation de la RDC.
            </p>

            {/* Navigation Tabs */}
            <div className="flex border-b border-rule mb-8 gap-4">
                <button
                    type="button"
                    onClick={() => setActiveTab('sof')}
                    className={`pb-3 text-sm font-medium border-b-2 transition-colors ${
                        activeTab === 'sof'
                            ? 'border-action text-action'
                            : 'border-transparent text-ink-muted hover:text-ink'
                    }`}
                >
                    <span className="flex items-center gap-2">
                        <RiBankCardLine size={16} /> 1. Source of Funds (Origine des fonds de l'opération)
                    </span>
                </button>
                <button
                    type="button"
                    onClick={() => setActiveTab('sow')}
                    className={`pb-3 text-sm font-medium border-b-2 transition-colors ${
                        activeTab === 'sow'
                            ? 'border-action text-action'
                            : 'border-transparent text-ink-muted hover:text-ink'
                    }`}
                >
                    <span className="flex items-center gap-2">
                        <RiSafe2Line size={16} /> 2. Source of Wealth (Patrimoine global)
                    </span>
                </button>
            </div>

            {activeTab === 'sof' ? (
                <div className="space-y-8">
                    {/* Declarations List */}
                    {declarations.length > 0 && (
                        <div className="border border-rule bg-surface p-4">
                            <h3 className="font-semibold text-sm mb-3">Déclarations SOF enregistrées</h3>
                            <div className="space-y-3">
                                {declarations.map((dec) => (
                                    <div key={dec.id} className="flex items-center justify-between border-b border-rule pb-3 last:border-b-0 text-sm">
                                        <div>
                                            <div className="font-bold text-ink">{dec.amountFormatted} — {dec.categoryLabel}</div>
                                            <div className="text-xs text-ink-muted mt-0.5">{dec.origin} {dec.bank && `(Banque : ${dec.bank})`}</div>
                                        </div>
                                        <span className={`text-xs px-2.5 py-1 border font-medium ${
                                            dec.isVerified ? 'bg-action/10 text-action border-action/30' : 'bg-surface-2 text-ink-muted border-rule'
                                        }`}>
                                            {dec.isVerified ? 'Justifié' : 'En attente d\'examen'}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* New SOF declaration form */}
                    <form onSubmit={submitSof} className="flex flex-col gap-6 border border-rule p-6 bg-surface">
                        <div className="border-b border-rule pb-2">
                            <h3 className="font-semibold text-ink text-base">Nouvelle Déclaration d'Origine des Fonds</h3>
                            <p className="text-xs text-ink-muted">Spécifiez la provenance précise du montant destiné à l'opération.</p>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <Field error={!!sofForm.errors.amount_minor}>
                                <Label>Montant (en sous-unités / cents) *</Label>
                                <Input
                                    type="number"
                                    value={sofForm.data.amount_minor}
                                    onChange={(e) => sofForm.setData('amount_minor', parseInt(e.target.value) || 0)}
                                />
                                <FieldError>{sofForm.errors.amount_minor}</FieldError>
                            </Field>

                            <Field error={!!sofForm.errors.currency}>
                                <Label>Devise *</Label>
                                <Input
                                    value={sofForm.data.currency}
                                    onChange={(e) => sofForm.setData('currency', e.target.value.toUpperCase())}
                                    maxLength={3}
                                />
                                <FieldError>{sofForm.errors.currency}</FieldError>
                            </Field>

                            <Field error={!!sofForm.errors.funds_category}>
                                <Label>Catégorie d'origine *</Label>
                                <select
                                    value={sofForm.data.funds_category}
                                    onChange={(e) => sofForm.setData('funds_category', e.target.value)}
                                    className="w-full border border-rule bg-surface p-2 text-sm text-ink"
                                >
                                    <option value="business_dividends">Dividendes & bénéfices d'entreprise</option>
                                    <option value="employment_income">Salaires & honoraires professionnels</option>
                                    <option value="asset_sale">Vente d'actif immobilier ou mobilier</option>
                                    <option value="company_revenue">Trésorerie d'exploitation</option>
                                    <option value="inheritance">Héritage / Succession</option>
                                    <option value="bank_financing">Financement ou crédit bancaire</option>
                                    <option value="investment_return">Plus-value d'investissement</option>
                                    <option value="other">Autre origine justifiée</option>
                                </select>
                                <FieldError>{sofForm.errors.funds_category}</FieldError>
                            </Field>
                        </div>

                        <Field error={!!sofForm.errors.origin_description}>
                            <Label>Description détaillée de la provenance *</Label>
                            <textarea
                                value={sofForm.data.origin_description}
                                onChange={(e) => sofForm.setData('origin_description', e.target.value)}
                                rows={3}
                                className="w-full border border-rule bg-surface p-2 text-sm text-ink"
                                placeholder="Détaillez le contexte économique de génération de ces fonds..."
                            />
                            <FieldError>{sofForm.errors.origin_description}</FieldError>
                        </Field>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <Field error={!!sofForm.errors.bank_name}>
                                <Label>Établissement bancaire d'origine</Label>
                                <Input
                                    value={sofForm.data.bank_name}
                                    onChange={(e) => sofForm.setData('bank_name', e.target.value)}
                                    placeholder="Ex: Rawbank, Equity BCDC, Ecobank..."
                                />
                                <FieldError>{sofForm.errors.bank_name}</FieldError>
                            </Field>

                            <Field error={!!sofForm.errors.account_iban_or_number}>
                                <Label>Numéro de compte ou IBAN (chiffré)</Label>
                                <Input
                                    value={sofForm.data.account_iban_or_number}
                                    onChange={(e) => sofForm.setData('account_iban_or_number', e.target.value)}
                                    placeholder="Numéro de compte bancaire d'émission"
                                />
                                <FieldError>{sofForm.errors.account_iban_or_number}</FieldError>
                            </Field>
                        </div>

                        <Field error={!!sofForm.errors.supporting_documents}>
                            <Label>Pièces justificatives (Relevés bancaires, contrats de vente, attestations)</Label>
                            <Input
                                type="file"
                                multiple
                                accept=".pdf,.jpg,.jpeg,.png"
                                onChange={(e) => sofForm.setData('supporting_documents', Array.from(e.target.files ?? []))}
                            />
                            <FieldError>{sofForm.errors.supporting_documents}</FieldError>
                        </Field>

                        <Button type="submit" disabled={sofForm.processing} className="self-start px-8 py-3">
                            {sofForm.processing ? 'Enregistrement...' : 'Enregistrer la déclaration SOF'}
                        </Button>
                    </form>
                </div>
            ) : (
                /* SOW Section */
                <form onSubmit={submitSow} className="flex flex-col gap-6 border border-rule p-6 bg-surface">
                    <div className="border-b border-rule pb-2">
                        <h3 className="font-semibold text-ink text-base">Déclaration du Patrimoine Global (Source of Wealth)</h3>
                        <p className="text-xs text-ink-muted">Historique général d'accumulation de votre patrimoine global.</p>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <Field error={!!sowForm.errors.estimated_total_net_worth_minor}>
                            <Label>Patrimoine net estimé (en sous-unités) *</Label>
                            <Input
                                type="number"
                                value={sowForm.data.estimated_total_net_worth_minor}
                                onChange={(e) => sowForm.setData('estimated_total_net_worth_minor', parseInt(e.target.value) || 0)}
                            />
                            <FieldError>{sowForm.errors.estimated_total_net_worth_minor}</FieldError>
                        </Field>

                        <Field error={!!sowForm.errors.currency}>
                            <Label>Devise *</Label>
                            <Input
                                value={sowForm.data.currency}
                                onChange={(e) => sowForm.setData('currency', e.target.value.toUpperCase())}
                                maxLength={3}
                            />
                            <FieldError>{sowForm.errors.currency}</FieldError>
                        </Field>
                    </div>

                    <Field error={!!sowForm.errors.wealth_description}>
                        <Label>Historique et activités génératrices de richesse *</Label>
                        <textarea
                            value={sowForm.data.wealth_description}
                            onChange={(e) => sowForm.setData('wealth_description', e.target.value)}
                            rows={4}
                            className="w-full border border-rule bg-surface p-2 text-sm text-ink"
                            placeholder="Exposez vos activités entrepreneuriales, participations dans des sociétés, patrimoine immobilier ou carrières professionnelles..."
                        />
                        <FieldError>{sowForm.errors.wealth_description}</FieldError>
                    </Field>

                    <Button type="submit" disabled={sowForm.processing} className="self-start px-8 py-3">
                        {sowForm.processing ? 'Enregistrement...' : 'Valider la déclaration de patrimoine'}
                    </Button>
                </form>
            )}
        </div>
    );
}
