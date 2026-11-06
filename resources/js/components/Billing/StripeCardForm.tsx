import {
    Elements,
    PaymentElement,
    useElements,
    useStripe,
} from '@stripe/react-stripe-js';
import { useEffect, useState } from 'react';
import { useStripePromise } from '@/hooks/useStripe';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import {
    stripeAppearance,
    stripePaymentElementOptions,
} from '@/lib/stripeAppearance';

/**
 * The actual card number / expiry / CVC fields the checkout page was
 * missing. These are rendered by Stripe's own hosted iframe
 * (PaymentElement) — the raw card data is typed directly into Stripe's
 * fields and never becomes a value in our React state, never gets
 * serialized into an Inertia form, never reaches our backend. That's
 * not a styling choice, it's what keeps this app out of PCI-DSS scope
 * for cardholder data entirely — building our own <input> for a card
 * number would be a real security regression, not just an inferior UX.
 */
export function StripeCardForm({
    intentUrl,
    onSuccess,
}: {
    intentUrl: string;
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
                'X-CSRF-TOKEN':
                    document.querySelector<HTMLMetaElement>(
                        'meta[name="csrf-token"]',
                    )?.content ?? '',
            },
        })
            .then(async (res) => {
                if (!res.ok) {
                    const body = await res.json().catch(() => null);
                    throw new Error(
                        body?.message ?? 'Impossible de préparer le paiement.',
                    );
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
            <div className="border border-danger/30 bg-danger/10 px-4 py-3 text-lg text-danger">
                Le paiement par carte n'est pas configuré sur cet environnement
                (clé publique Stripe manquante).
            </div>
        );
    }

    if (loadError) {
        return (
            <div className="border border-danger/30 bg-danger/10 px-4 py-3 text-lg text-danger">
                {loadError}
            </div>
        );
    }

    if (!clientSecret) {
        return (
            <Skeleton className="px-4 py-6 text-center text-caption">
                Préparation du formulaire de paiement...
            </Skeleton>
        );
    }

    return (
        <Elements
            stripe={stripePromise}
            options={{ clientSecret, appearance: stripeAppearance }}
        >
            <InnerForm onSuccess={onSuccess} />
        </Elements>
    );
}

function InnerForm({ onSuccess }: { onSuccess: () => void }) {
    const stripe = useStripe();
    const elements = useElements();
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);

    async function submit(e: React.FormEvent) {
        e.preventDefault();
        if (!stripe || !elements) return;

        setSubmitting(true);
        setError(null);

        // redirect: 'if_required' keeps the user on this page unless
        // their card actually requires 3D Secure / another redirect step
        // — most test/standard cards resolve without ever leaving here.
        const { error: confirmError, paymentIntent } =
            await stripe.confirmPayment({
                elements,
                redirect: 'if_required',
                confirmParams: {
                    return_url: window.location.href,
                },
            });

        setSubmitting(false);

        if (confirmError) {
            setError(confirmError.message ?? 'Le paiement a échoué.');
            return;
        }

        if (
            paymentIntent?.status === 'succeeded' ||
            paymentIntent?.status === 'processing'
        ) {
            onSuccess();
        }
    }

    return (
        <form onSubmit={submit} className="flex flex-col gap-4">
            <PaymentElement options={stripePaymentElementOptions} />

            {error && (
                <div className="border border-danger/30 bg-danger/10 px-3 py-2 text-lg text-danger">
                    {error}
                </div>
            )}

            <Button
                type="submit"
                variant="cta"
                disabled={!stripe || submitting}
                className="px-6 py-3"
            >
                {submitting ? 'Traitement...' : 'Payer'}
            </Button>
        </form>
    );
}
