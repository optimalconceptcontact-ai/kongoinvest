import type { Appearance } from '@stripe/stripe-js';

/**
 * Shared between StripeCardForm (Escrow) and StripeSetupForm (subscription
 * checkout) so every Stripe Elements form in the app looks the same —
 * square/flat like the rest of the UI, monospace to match the app's type
 * system (Anonymous Pro is already loaded app-wide, see app.blade.php).
 */
export const stripeAppearance: Appearance = {
    theme: 'flat',
    labels: 'above',
    inputs: 'condensed',
    variables: {
        borderRadius: '0px',
        colorPrimary: '#0f172a',
        fontFamily:
            "'Anonymous Pro', ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace",
    },
};

/** Passed to PaymentElement's own `options`, not the Elements `appearance`. */
export const stripePaymentElementOptions = {
    layout: 'tabs' as const,
};
