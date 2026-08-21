<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;

/**
 * The normalized event every provider's handleWebhook() produces after
 * signature verification — ProcessWebhookEvent works with this shape
 * only and never touches a provider's raw payload format directly.
 */
final class WebhookEvent
{
    public function __construct(
        public readonly string $providerEventId,
        public readonly string $providerReference, // ties back to Payment::provider_reference
        public readonly PaymentStatus $status,
        public readonly string $type, // e.g. 'payment_intent.succeeded' — kept for the audit trail
        public readonly ?int $amountMinor = null,
        public readonly ?string $currency = null,
        public readonly array $rawPayload = [],
    ) {}
}
