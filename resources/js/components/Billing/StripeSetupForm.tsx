import { Elements, PaymentElement, useElements, useStripe } from '@stripe/react-stripe-js';
import { useEffect, useState } from 'react';
import { useStripePromise } from '@/hooks/useStripe';
import { Button } from '@/components/Ui/Button';
import { stripeAppearance, stripePaymentElementOptions } from '@/lib/stripeAppearance';

/**
 * Subscription checkout, not a one-off charge — mounts against a
 * SetupIntent (saves a reusable payment method) rather than a
 * PaymentIntent, then hands the resulting payment method ID to the
 * backend to actually create the Cashier subscription. Deliberately a
 * separate component from StripeCardForm, which is shared with the
 * one-off Escrow PaymentIntent flow and must keep working unchanged.
 */
export function StripeSetupForm({
    intentUrl,
    subscribeUrl,
    billingPeriod,
    onSuccess,
}: {
    intentUrl: string;
    subscribeUrl: string;
    billingPeriod: 'monthly' | 'annual';
    onSuccess: () => void;
}) {
    const stripePromise = useStripePromise();
    const [clientSecret, setClientSecret] = useState<string | null>(null);
    const [loadError, setLoadError] = useState<string | null>(null);

    useEffect(() => {
        let cancelled = false;

        fetch(intentUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
            },
        })
            .then(async (res) => {
                if (!res.ok) {
                    const body = await res.json().catch(() => null);
                    throw new Error(body?.message ?? 'Impossible de préparer le paiement.');
                }
                return res.json();
            })
            .then((data) => {
                if (!cancelled) setClientSecret(data.clientSecret);
            })
            .catch((err) => {
                if (!cancelled) setLoadError(err.message);
            });

        return () => {
            cancelled = true;
        };
    }, [intentUrl]);

    if (!stripePromise) {
        return (
            <div className="border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                Le paiement par carte n'est pas configuré sur cet environnement (clé publique Stripe manquante).
            </div>
        );
    }

    if (loadError) {
        return <div className="border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{loadError}</div>;
    }

    if (!clientSecret) {
        return <div className="animate-pulse border border-slate-200 bg-slate-50 px-4 py-6 text-center text-caption">Préparation du formulaire de paiement...</div>;
    }

    return (
        <Elements stripe={stripePromise} options={{ clientSecret, appearance: stripeAppearance }}>
            <InnerForm subscribeUrl={subscribeUrl} billingPeriod={billingPeriod} onSuccess={onSuccess} />
        </Elements>
    );
}

function InnerForm({
    subscribeUrl,
    billingPeriod,
    onSuccess,
}: {
    subscribeUrl: string;
    billingPeriod: 'monthly' | 'annual';
    onSuccess: () => void;
}) {
    const stripe = useStripe();
    const elements = useElements();
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);

    async function submit(e: React.FormEvent) {
        e.preventDefault();
        if (!stripe || !elements) return;

        setSubmitting(true);
        setError(null);

        const { error: confirmError, setupIntent } = await stripe.confirmSetup({
            elements,
            redirect: 'if_required',
            confirmParams: {
                return_url: window.location.href,
            },
        });

        if (confirmError) {
            setSubmitting(false);
            setError(confirmError.message ?? "L'enregistrement de la carte a échoué.");
            return;
        }

        const paymentMethod = setupIntent?.payment_method;

        if (setupIntent?.status !== 'succeeded' || !paymentMethod) {
            setSubmitting(false);
            setError("L'enregistrement de la carte a échoué.");
            return;
        }

        try {
            const res = await fetch(subscribeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({ payment_method: paymentMethod, billing_period: billingPeriod }),
            });

            const data = await res.json();

            if (!res.ok) {
                throw new Error(data?.message ?? "L'abonnement n'a pas pu être activé.");
            }

            if (data.requiresAction) {
                // Cashier's own SCA/3DS confirmation page for the subscription's
                // first invoice — distinct from the SetupIntent's own 3DS,
                // which confirmSetup() above already handled for saving the card.
                window.location.href = `/stripe/payment/${data.paymentId}?redirect=${encodeURIComponent('/settings/subscription')}`;
                return;
            }

            onSuccess();
        } catch (err) {
            setError(err instanceof Error ? err.message : "L'abonnement n'a pas pu être activé.");
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <form onSubmit={submit} className="flex flex-col gap-4">
            <PaymentElement options={stripePaymentElementOptions} />

            {error && <div className="border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{error}</div>}

            <Button type="submit" variant="cta" disabled={!stripe || submitting} className="px-6 py-3">
                {submitting ? 'Traitement...' : "S'abonner"}
            </Button>
        </form>
    );
}
