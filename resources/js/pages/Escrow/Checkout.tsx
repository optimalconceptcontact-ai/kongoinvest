import { Head, router, useForm } from '@inertiajs/react';
import { RiExternalLinkLine } from '@remixicon/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { StripeCardForm } from '@/components/Billing/StripeCardForm';
import { BackLink } from '@/components/ui/BackLink';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/Field';
import { FieldError } from '@/components/ui/FieldError';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/Label';
import { PhoneInput } from '@/components/ui/PhoneInput';

type EscrowSummary = {
    id: number;
    deal_room_id: number;
    amount_minor: number;
    currency: string;
};
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

export default function Checkout({
    escrow,
    fee,
}: {
    escrow: EscrowSummary;
    fee: FeeBreakdown;
}) {
    const [provider, setProvider] = useState<string>('stripe');
    const [paid, setPaid] = useState(false);
    const { data, setData, post, processing, errors } = useForm({
        provider: 'stripe',
        phone_number: '',
    });

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
                <p className="mb-2 text-eyebrow">Paiement confirmé</p>
                <h1 className="mb-4 text-page-title">Séquestre financé</h1>
                <p className="mb-8 text-body">
                    Les fonds seront marqués comme retenus dès la confirmation
                    du fournisseur de paiement (généralement quelques secondes).
                </p>
                <Button
                    onClick={() =>
                        router.visit(`/deal-rooms/${escrow.deal_room_id}`)
                    }
                    className="px-6 py-3"
                >
                    Retour à la Deal Room
                </Button>
            </div>
        );
    }

    return (
        <div className="mx-auto max-w-md px-6 py-16">
            <Head title="Financer le séquestre" />
            <BackLink href={`/deal-rooms/${escrow.deal_room_id}`} />
            <p className="text-eyebrow">Séquestre (Escrow)</p>
            <h1 className="mt-2 mb-1 text-page-title">Financer le séquestre</h1>

            <div className="mb-6 border border-rule bg-surface-2 px-4 py-3 text-lg">
                <div className="flex items-center justify-between text-ink">
                    <span>Montant investissement</span>
                    <span className="font-mono">
                        {formatMinor(escrow.amount_minor)} {escrow.currency}
                    </span>
                </div>
                <div className="mt-1 flex items-center justify-between text-ink">
                    <span>
                        Frais d'accompagnement Coaching (
                        {(fee.rate * 100).toLocaleString('fr-FR')}%)
                    </span>
                    <span className="font-mono">
                        {formatMinor(fee.feeMinor)} {escrow.currency}
                    </span>
                </div>
                <div className="mt-2 flex items-center justify-between border-t border-rule pt-2 font-bold text-ink">
                    <span>Total à payer</span>
                    <span className="font-mono">
                        {formatMinor(fee.totalMinor)} {escrow.currency}
                    </span>
                </div>
            </div>

            <p className="mb-8 text-body">
                Les fonds sont retenus par le fournisseur de paiement jusqu'à ce
                que vous confirmiez leur libération, ou jusqu'à un
                remboursement. KongoInvest gère l'accompagnement de la
                relation investisseur/entrepreneur jusqu'à la conclusion de
                l'investissement (plan Coaching) — les frais ci-dessus couvrent
                ce service.
            </p>

            {errors.provider && (
                <div className="mb-6 border border-danger/30 bg-danger/10 px-4 py-3 text-lg text-danger">
                    {errors.provider}
                </div>
            )}

            <fieldset className="mb-6">
                <legend className="text-form-label mb-2">
                    Moyen de paiement
                </legend>
                <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    {providers.map((p) => (
                        <button
                            type="button"
                            key={p.value}
                            onClick={() => selectProvider(p.value)}
                            aria-pressed={provider === p.value}
                            className={`min-h-11 border px-3 py-2.5 text-btn ${provider === p.value ? 'border-ink bg-ink text-paper' : 'border-rule-strong text-ink hover:border-ink'}`}
                        >
                            {p.label}
                        </button>
                    ))}
                </div>
            </fieldset>

            {provider === 'stripe' && (
                <StripeCardForm
                    intentUrl={`/escrow/${escrow.id}/checkout/intent`}
                    onSuccess={() => setPaid(true)}
                />
            )}

            {provider === 'paypal' && (
                <form
                    onSubmit={submitRedirectFlow}
                    className="flex flex-col gap-4"
                >
                    <div className="flex items-start gap-2 border border-rule bg-surface-2 px-4 py-3 text-lg text-ink">
                        <RiExternalLinkLine
                            size={16}
                            className="mt-0.5 flex-shrink-0 text-ink-muted"
                        />
                        Vous allez être redirigé vers PayPal pour vous connecter
                        et confirmer le paiement en toute sécurité. Vous
                        reviendrez sur KongoInvest une fois le paiement
                        terminé.
                    </div>
                    <Button
                        type="submit"
                        variant="cta"
                        disabled={processing}
                        className="px-6 py-3"
                    >
                        {processing
                            ? 'Redirection...'
                            : 'Continuer avec PayPal'}
                    </Button>
                </form>
            )}

            {mobileMoneyValues.includes(provider) && (
                <form
                    onSubmit={submitRedirectFlow}
                    className="flex flex-col gap-4"
                >
                    <Field error={!!errors.phone_number}>
                        <Label>Numéro de téléphone</Label>
                        <PhoneInput
                            autoComplete="tel"
                            value={data.phone_number}
                            onChange={(phone_number) =>
                                setData('phone_number', phone_number)
                            }
                            placeholder="812 345 678"
                            required
                            error={!!errors.phone_number}
                        />
                        <FieldError>{errors.phone_number}</FieldError>
                        <p className="mt-1 text-caption">
                            Une demande de confirmation sera envoyée sur ce
                            numéro.
                        </p>
                    </Field>
                    <Button
                        type="submit"
                        variant="cta"
                        disabled={processing}
                        className="px-6 py-3"
                    >
                        {processing ? 'Envoi...' : 'Demander le paiement'}
                    </Button>
                </form>
            )}

            <p className="mt-6 text-center text-caption">
                Le paiement est traité par le fournisseur choisi.
                KongoInvest ne stocke jamais vos coordonnées bancaires.
            </p>
        </div>
    );
}
