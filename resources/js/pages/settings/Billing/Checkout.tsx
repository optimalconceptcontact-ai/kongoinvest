import { Head, router, useForm } from '@inertiajs/react';
import { RiExternalLinkLine } from '@remixicon/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { StripeSetupForm } from '@/components/Billing/StripeSetupForm';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/Field';
import { FieldError } from '@/components/ui/FieldError';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/Label';
import { PhoneInput } from '@/components/ui/PhoneInput';
import { PublicLayout } from '@/layouts/PublicLayout';

type Plan = {
    id: number;
    key: string;
    name: string;
    price_minor: number;
    annual_price_minor: number | null;
    currency: string;
};
type BillingPeriod = 'monthly' | 'annual';

const providers = [
    { value: 'stripe', label: 'Carte bancaire' },
    { value: 'paypal', label: 'PayPal' },
    { value: 'orange_money', label: 'Orange Money' },
    { value: 'mpesa', label: 'M-Pesa' },
    { value: 'airtel_money', label: 'Airtel Money' },
] as const;

const mobileMoneyValues = ['orange_money', 'mpesa', 'airtel_money'];

function formatAmount(amountMinor: number, currency: string) {
    return `${(amountMinor / 100).toLocaleString('fr-FR')} ${currency}`;
}

export default function Checkout({ plan }: { plan: Plan }) {
    const [provider, setProvider] = useState<string>('stripe');
    const [billingPeriod, setBillingPeriod] =
        useState<BillingPeriod>('monthly');
    const [paid, setPaid] = useState(false);
    const { data, setData, post, processing, errors } = useForm({
        provider: 'stripe',
        phone_number: '',
        billing_period: 'monthly' as BillingPeriod,
    });

    function selectProvider(value: string) {
        setProvider(value);
        setData('provider', value);
    }

    function selectBillingPeriod(value: BillingPeriod) {
        setBillingPeriod(value);
        setData('billing_period', value);
    }

    function submitRedirectFlow(e: FormEvent) {
        e.preventDefault();
        post(`/settings/subscription/${plan.id}/checkout`);
    }

    const amountMinor =
        billingPeriod === 'annual' && plan.annual_price_minor
            ? plan.annual_price_minor
            : plan.price_minor;
    const monthlyEquivalentMinor =
        billingPeriod === 'annual' && plan.annual_price_minor
            ? Math.round(plan.annual_price_minor / 12)
            : null;

    if (paid) {
        return (
            <div className="mx-auto max-w-md px-6 py-16 text-center">
                <Head title="Paiement confirmé" />
                <p className="mb-2 text-eyebrow">
                    {provider === 'stripe'
                        ? 'Essai démarré'
                        : 'Paiement confirmé'}
                </p>
                <h1 className="mb-4 text-page-title">Merci !</h1>
                <p className="mb-8 text-body">
                    {provider === 'stripe'
                        ? "Votre essai de 30 jours a démarré. Votre carte ne sera prélevée qu'à la fin de l'essai."
                        : 'Votre paiement a été reçu. Votre abonnement sera activé dès la confirmation du fournisseur de paiement (généralement quelques secondes).'}
                </p>
                <Button
                    onClick={() => router.visit('/settings/subscription')}
                    className="px-6 py-3"
                >
                    Voir mon abonnement
                </Button>
            </div>
        );
    }

    return (
        <PublicLayout>
            <div className="mx-auto max-w-lg px-6 py-16">
                <Head title={`Paiement — ${plan.name}`} />
                <p className="text-eyebrow">Paiement</p>
                <h1 className="mt-2 mb-1 text-page-title">
                    Passer au plan {plan.name}
                </h1>

                {plan.annual_price_minor && (
                    <div className="mb-4 inline-flex border border-rule-strong">
                        <button
                            type="button"
                            onClick={() => selectBillingPeriod('monthly')}
                            aria-pressed={billingPeriod === 'monthly'}
                            className={`min-h-11 px-4 py-2 text-btn ${billingPeriod === 'monthly' ? 'bg-ink text-paper' : 'text-ink hover:bg-surface-2'}`}
                        >
                            Mensuel
                        </button>
                        <button
                            type="button"
                            onClick={() => selectBillingPeriod('annual')}
                            aria-pressed={billingPeriod === 'annual'}
                            className={`min-h-11 px-4 py-2 text-btn ${billingPeriod === 'annual' ? 'bg-ink text-paper' : 'text-ink hover:bg-surface-2'}`}
                        >
                            Annuel — 2 mois offerts
                        </button>
                    </div>
                )}

                <p className="mb-8 font-mono text-2xl font-bold text-ink">
                    {formatAmount(amountMinor, plan.currency)}/
                    {billingPeriod === 'annual' ? 'an' : 'mois'}
                    {monthlyEquivalentMinor && (
                        <span className="ml-2 font-sans text-lg font-normal text-ink-muted">
                            (soit{' '}
                            {formatAmount(
                                monthlyEquivalentMinor,
                                plan.currency,
                            )}
                            /mois)
                        </span>
                    )}
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

                {/* Card — via Cashier (SetupIntent + real subscription), auto-renews monthly. */}
                {provider === 'stripe' && (
                    <>
                        <div className="mb-4 border border-rule-strong bg-surface-2 px-4 py-3 text-lg leading-5 text-ink-muted">
                            30 jours d'essai gratuit — votre carte est
                            enregistrée maintenant mais n'est prélevée qu'à la
                            fin de l'essai. Annulez à tout moment avant pour ne
                            rien payer.
                        </div>
                        <StripeSetupForm
                            intentUrl={`/settings/subscription/${plan.id}/checkout/intent`}
                            subscribeUrl={`/settings/subscription/${plan.id}/checkout/subscribe`}
                            billingPeriod={billingPeriod}
                            onSuccess={() => setPaid(true)}
                        />
                    </>
                )}

                {/* PayPal — nothing to collect on our side; be explicit about what happens next instead of showing an empty form. */}
                {provider === 'paypal' && (
                    <form
                        onSubmit={submitRedirectFlow}
                        className="flex flex-col gap-4"
                    >
                        <div className="flex items-start gap-2 border border-rule bg-surface-2 px-4 py-3 text-caption">
                            <RiExternalLinkLine
                                size={16}
                                className="mt-0.5 flex-shrink-0 text-ink-muted"
                            />
                            Vous allez être redirigé vers PayPal pour vous
                            connecter et confirmer le paiement en toute
                            sécurité. Vous reviendrez sur KongoInvest une
                            fois le paiement terminé.
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

                {/* Mobile Money — the one rail that genuinely needs a field from us: the phone number to charge. */}
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

                {/* PayPal/Mobile Money charge once per checkout and don't auto-renew — only
                    the card rail (via Cashier) rebills automatically each month, and only
                    the card rail can offer the 30-day trial since it's the one rail Cashier
                    actually delays billing on. */}
                {provider !== 'stripe' && (
                    <p className="mt-4 text-center text-caption">
                        Ce moyen de paiement facture immédiatement (pas d'essai)
                        et ne se renouvelle pas automatiquement.
                    </p>
                )}

                <p className="mt-6 text-center text-caption">
                    Le paiement est traité par le fournisseur choisi.
                    KongoInvest ne stocke jamais vos coordonnées bancaires.
                </p>
            </div>
        </PublicLayout>
    );
}
