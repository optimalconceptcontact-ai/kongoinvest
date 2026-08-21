<?php

namespace App\Contracts;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Payments\PaymentIntentResult;
use App\Services\Payments\RefundResult;
use App\Services\Payments\WebhookEvent;
use Illuminate\Http\Request;

/**
 * Spec §36, §96: the Payment domain never depends directly on Stripe or
 * any single provider. Every concrete provider (StripePaymentProvider,
 * PayPalPaymentProvider, OrangeMoneyProvider, ...) implements this same
 * five-method contract; nothing outside app/Services/Payments/Providers
 * ever references a provider class by name — see
 * PaymentProviderFactory, which is the only place that resolves one.
 */
interface PaymentProviderInterface
{
    /**
     * Initiates the payment with the provider and returns whatever the
     * frontend needs to complete it (a client secret for an embedded
     * card form, a redirect URL for PayPal/Mobile Money, etc). Must set
     * $payment->provider_reference before returning.
     */
    public function createPayment(Payment $payment): PaymentIntentResult;

    /** Re-queries the provider directly — used when a webhook is late/missing, not as the primary status path. */
    public function verifyPayment(Payment $payment): PaymentStatus;

    public function refund(Payment $payment, ?int $amountMinor = null): RefundResult;

    public function getStatus(Payment $payment): PaymentStatus;

    /**
     * Verifies the webhook's signature against the raw request and, if
     * valid, returns a normalized WebhookEvent. Returns null — never
     * throws — on an invalid signature; the caller (WebhookController)
     * treats null as "reject with 400," not as an application error
     * (spec §40: webhook signatures are always verified before anything
     * else happens).
     */
    public function handleWebhook(Request $request): ?WebhookEvent;
}
