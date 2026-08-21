import { usePage } from '@inertiajs/react';
import { loadStripe, type Stripe } from '@stripe/stripe-js';
import { useMemo } from 'react';

type SharedStripeKey = { stripePublishableKey: string | null };

/**
 * loadStripe() must only ever be called once per publishable key (it
 * loads js.stripe.com/v3 and caches internally) — memoized per key so
 * remounting a checkout form doesn't reload the script.
 */
const promiseCache = new Map<string, Promise<Stripe | null>>();

export function useStripePromise() {
    const { props } = usePage<SharedStripeKey>();
    const key = props.stripePublishableKey;

    return useMemo(() => {
        if (!key) return null;
        if (!promiseCache.has(key)) {
            promiseCache.set(key, loadStripe(key));
        }
        return promiseCache.get(key)!;
    }, [key]);
}
