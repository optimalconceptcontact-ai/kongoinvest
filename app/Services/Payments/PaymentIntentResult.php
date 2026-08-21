<?php

namespace App\Services\Payments;

/** What createPayment() hands back to the controller/frontend. */
final class PaymentIntentResult
{
    public function __construct(
        public readonly string $providerReference,
        // For card rails (Stripe): a client secret the frontend's SDK
        // uses to complete the payment. Null for redirect-based flows.
        public readonly ?string $clientSecret = null,
        // For redirect-based flows (PayPal, Mobile Money): where to send
        // the payer. Null for embedded-form flows.
        public readonly ?string $redirectUrl = null,
    ) {}
}
