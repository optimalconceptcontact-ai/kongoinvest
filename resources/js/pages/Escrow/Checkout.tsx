import { Head, router, useForm } from '@inertiajs/react';
import { RiExternalLinkLine } from '@remixicon/react';
import { FormEvent, useState } from 'react';
import { StripeCardForm } from '@/components/Billing/StripeCardForm';
import { Button } from '@/components/Ui/Button';
import { FieldError } from '@/components/Ui/FieldError';
import { Input } from '@/components/Ui/Input';
import { Label } from '@/components/Ui/Label';

type EscrowSummary = { id: number; amount_minor: number; currency: string };
type FeeBreakdown = { rate: number; feeMinor: number; totalMinor: number };

const providers = [
    { value: 'stripe', label: 'Carte bancaire' },
    { value: 'paypal', label: 'PayPal' },
    { value: 'orange_money', label: 'Orange Money' },
    { value: 'mpesa', label: 'M-Pesa' },
    { value: 'airtel_money', label: 'Airtel Money' },
] as const;

const mobileMoneyValues = ['orange_money', 'mpesa', 'airtel_money'];

function formatMinor(amountMinor: number) {
    return (amountMinor / 100).toLocaleString('fr-FR');
}

export default function Checkout({ escrow, fee }: { escrow: EscrowSummary; fee: FeeBreakdown }) {
    const [provider, setProvider] = useState<string>('stripe');
    const [paid, setPaid] = useState(false);
    const { data, setData, post, processing, errors } = useForm({ provider: 'paypal', phone_number: '' });

    function selectProvider(value: string) {
        setProvider(value);
        setData('provider', value);
    }

    function submitRedirectFlow(e: FormEvent) {
        e.preventDefault();
        post(`/escrow/${escrow.id}/checkout`);
    }

    if (paid) {
        return (
            <div className="mx-auto max-w-md px-6 py-16 text-center">
                <Head title="Paiement confirmé" />
                <p className="mb-2 font-mono text-xs uppercase tracking-widest text-blue-600">Paiement confirmé</p>
                <h1 className="mb-4 text-page-title">Séquestre financé</h1>
                <p className="mb-8 text-body">
                    Les fonds seront marqués comme retenus dès la confirmation du fournisseur de paiement
                    (généralement quelques secondes).
                </p>
                <Button onClick={() => router.visit(`/deal-rooms/${escrow.id}`)} className="px-6 py-3">
                    Retour à la Deal Room
                </Button>
            </div>
        );
    }

    return (
        <div className="mx-auto max-w-md px-6 py-16">
            <Head title="Financer le séquestre" />
            <p className="text-eyebrow">Séquestre (Escrow)</p>
            <h1 className="mt-2 mb-1 text-page-title">Financer le séquestre</h1>

            <div className="mb-6 border border-slate-200 bg-slate-50 px-4 py-3 text-sm">
                <div className="flex items-center justify-between text-slate-700">
                    <span>Montant investissement</span>
                    <span className="font-mono">
                        {formatMinor(escrow.amount_minor)} {escrow.currency}
                    </span>
                </div>
                <div className="mt-1 flex items-center justify-between text-slate-700">
                    <span>Frais d'accompagnement Coaching ({(fee.rate * 100).toLocaleString('fr-FR')}%)</span>
                    <span className="font-mono">
                        {formatMinor(fee.feeMinor)} {escrow.currency}
                    </span>
                </div>
                <div className="mt-2 flex items-center justify-between border-t border-slate-300 pt-2 font-bold text-slate-900">
                    <span>Total à payer</span>
                    <span className="font-mono">
                        {formatMinor(fee.totalMinor)} {escrow.currency}
                    </span>
                </div>
            </div>

            <p className="mb-8 text-body">
                Les fonds sont retenus par le fournisseur de paiement jusqu'à ce que vous confirmiez leur
                libération, ou jusqu'à un remboursement (spec §33-35). KongoInvestment gère l'accompagnement de
                la relation investisseur/entrepreneur jusqu'à la conclusion de l'investissement (plan Coaching)
                — les frais ci-dessus couvrent ce service.
            </p>

            {errors.provider && (
                <div className="mb-6 border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-700">{errors.provider}</div>
            )}

            <div className="mb-6">
                <label className="mb-2 block text-sm font-medium text-slate-700">Moyen de paiement</label>
                <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    {providers.map((p) => (
                        <button
                            type="button"
                            key={p.value}
                            onClick={() => selectProvider(p.value)}
                            className={`border px-3 py-2.5 text-xs font-medium ${provider === p.value ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-400 text-slate-700 hover:border-slate-900'}`}
                        >
                            {p.label}
                        </button>
                    ))}
                </div>
            </div>

            {provider === 'stripe' && (
                <StripeCardForm intentUrl={`/escrow/${escrow.id}/checkout/intent`} onSuccess={() => setPaid(true)} />
            )}

            {provider === 'paypal' && (
                <form onSubmit={submitRedirectFlow} className="flex flex-col gap-4">
                    <div className="flex items-start gap-2 border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-700">
                        <RiExternalLinkLine size={16} className="mt-0.5 flex-shrink-0 text-slate-400" />
                        Vous allez être redirigé vers PayPal pour vous connecter et confirmer le paiement en toute
                        sécurité. Vous reviendrez sur KongoInvestment une fois le paiement terminé.
                    </div>
                    <Button type="submit" variant="cta" disabled={processing} className="px-6 py-3">
                        {processing ? 'Redirection...' : 'Continuer avec PayPal'}
                    </Button>
                </form>
            )}

            {mobileMoneyValues.includes(provider) && (
                <form onSubmit={submitRedirectFlow} className="flex flex-col gap-4">
                    <div>
                        <Label>Numéro de téléphone</Label>
                        <Input
                            value={data.phone_number}
                            onChange={(e) => setData('phone_number', e.target.value)}
                            placeholder="+243..."
                            error={!!errors.phone_number}
                        />
                        <FieldError>{errors.phone_number}</FieldError>
                        <p className="mt-1 text-caption">Une demande de confirmation sera envoyée sur ce numéro.</p>
                    </div>
                    <Button type="submit" variant="cta" disabled={processing} className="px-6 py-3">
                        {processing ? 'Envoi...' : 'Demander le paiement'}
                    </Button>
                </form>
            )}

            <p className="mt-6 text-center text-caption">
                Le paiement est traité par le fournisseur choisi. KongoInvestment ne stocke jamais vos coordonnées
                bancaires.
            </p>
        </div>
    );
}
