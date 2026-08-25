import { Head, router, useForm } from '@inertiajs/react';
import { RiExternalLinkLine } from '@remixicon/react';
import { FormEvent, useState } from 'react';
import { StripeSetupForm } from '@/components/Billing/StripeSetupForm';
import { Button } from '@/components/Ui/Button';
import { FieldError } from '@/components/Ui/FieldError';
import { Input } from '@/components/Ui/Input';
import { Label } from '@/components/Ui/Label';
import { PublicLayout } from '@/layouts/PublicLayout';

type Plan = { id: number; key: string; name: string; price_minor: number; annual_price_minor: number | null; currency: string };
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
    const [billingPeriod, setBillingPeriod] = useState<BillingPeriod>('monthly');
    const [paid, setPaid] = useState(false);
    const { data, setData, post, processing, errors } = useForm({ provider: 'paypal', phone_number: '', billing_period: 'monthly' as BillingPeriod });

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

    const amountMinor = billingPeriod === 'annual' && plan.annual_price_minor ? plan.annual_price_minor : plan.price_minor;
    const monthlyEquivalentMinor = billingPeriod === 'annual' && plan.annual_price_minor ? Math.round(plan.annual_price_minor / 12) : null;

    if (paid) {
        return (
            <div className="mx-auto max-w-md px-6 py-16 text-center">
                <Head title="Paiement confirmé" />
                <p className="mb-2 font-mono text-xs uppercase tracking-widest text-blue-600">Paiement confirmé</p>
                <h1 className="mb-4 text-page-title">Merci !</h1>
                <p className="mb-8 text-body">
                    Votre paiement a été reçu. Votre abonnement sera activé dès la confirmation du fournisseur de
                    paiement (généralement quelques secondes).
                </p>
                <Button onClick={() => router.visit('/settings/subscription')} className="px-6 py-3">
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
                <h1 className="mt-2 mb-1 text-page-title">Passer au plan {plan.name}</h1>

                {plan.annual_price_minor && (
                    <div className="mb-4 inline-flex border border-slate-400">
                        <button
                            type="button"
                            onClick={() => selectBillingPeriod('monthly')}
                            className={`px-4 py-2 text-sm font-semibold ${billingPeriod === 'monthly' ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-50'}`}
                        >
                            Mensuel
                        </button>
                        <button
                            type="button"
                            onClick={() => selectBillingPeriod('annual')}
                            className={`px-4 py-2 text-sm font-semibold ${billingPeriod === 'annual' ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-50'}`}
                        >
                            Annuel — 2 mois offerts
                        </button>
                    </div>
                )}

                <p className="mb-8 font-mono text-lg font-bold text-slate-900">
                    {formatAmount(amountMinor, plan.currency)}/{billingPeriod === 'annual' ? 'an' : 'mois'}
                    {monthlyEquivalentMinor && (
                        <span className="ml-2 font-sans text-sm font-normal text-slate-500">
                            (soit {formatAmount(monthlyEquivalentMinor, plan.currency)}/mois)
                        </span>
                    )}
                </p>

                {errors.provider && (
                    <div className="mb-6 border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-700">{errors.provider}</div>
                )}

                <div className="mb-6">
                    <Label className="mb-2">Moyen de paiement</Label>
                    <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        {providers.map((p) => (
                            <button
                                type="button"
                                key={p.value}
                                onClick={() => selectProvider(p.value)}
                                className={`border px-3 py-2.5 text-sm font-semibold ${provider === p.value ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-400 text-slate-700 hover:border-slate-900'}`}
                            >
                                {p.label}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Card — via Cashier (SetupIntent + real subscription), auto-renews monthly. */}
                {provider === 'stripe' && (
                    <StripeSetupForm
                        intentUrl={`/settings/subscription/${plan.id}/checkout/intent`}
                        subscribeUrl={`/settings/subscription/${plan.id}/checkout/subscribe`}
                        billingPeriod={billingPeriod}
                        onSuccess={() => setPaid(true)}
                    />
                )}

                {/* PayPal — nothing to collect on our side; be explicit about what happens next instead of showing an empty form. */}
                {provider === 'paypal' && (
                    <form onSubmit={submitRedirectFlow} className="flex flex-col gap-4">
                        <div className="flex items-start gap-2 border border-slate-300 bg-slate-50 px-4 py-3 text-caption">
                            <RiExternalLinkLine size={16} className="mt-0.5 flex-shrink-0 text-slate-400" />
                            Vous allez être redirigé vers PayPal pour vous connecter et confirmer le paiement en toute
                            sécurité. Vous reviendrez sur KongoInvestment une fois le paiement terminé.
                        </div>
                        <Button type="submit" variant="cta" disabled={processing} className="px-6 py-3">
                            {processing ? 'Redirection...' : 'Continuer avec PayPal'}
                        </Button>
                    </form>
                )}

                {/* Mobile Money — the one rail that genuinely needs a field from us: the phone number to charge. */}
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

                {/* PayPal/Mobile Money charge once per checkout and don't auto-renew — only
                    the card rail (via Cashier) rebills automatically each month. */}
                {provider !== 'stripe' && (
                    <p className="mt-4 text-center text-caption">
                        Ce moyen de paiement facture une fois ; le renouvellement mensuel n'est pas automatique.
                    </p>
                )}

                <p className="mt-6 text-center text-caption">
                    Le paiement est traité par le fournisseur choisi. KongoInvestment ne stocke jamais vos coordonnées
                    bancaires.
                </p>
            </div>

        </PublicLayout>
    );
}
