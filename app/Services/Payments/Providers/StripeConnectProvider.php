<?php

namespace App\Services\Payments\Providers;

use App\Contracts\PaymentProviderInterface;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Payments\PaymentIntentResult;
use App\Services\Payments\RefundResult;
use App\Services\Payments\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Spec §37: Stripe Connect is for marketplace flows where funds need to
 * reach a beneficiary's own connected account (e.g. releasing Escrow
 * funds to an entrepreneur, Phase 11) — not a substitute for a plain
 * internal ledger. Eligibility (country, connected-account
 * verification status) must be checked before use; this provider
 * assumes the caller already confirmed that, it doesn't re-derive
 * eligibility rules itself.
 */
class StripeConnectProvider implements PaymentProviderInterface
{
    public function __construct(private StripePaymentProvider $stripe) {}

    public function createPayment(Payment $payment): PaymentIntentResult
    {
        $destinationAccountId = $payment->metadata['destination_account_id'] ?? null;

        if (! $destinationAccountId) {
            throw new \InvalidArgumentException('Stripe Connect payments require metadata.destination_account_id (the beneficiary\'s connected account).');
        }

        $response = Http::withToken(config('services.stripe.secret'))
            ->asForm()
            ->post('https://api.stripe.com/v1/payment_intents', [
                'amount' => $payment->amount_minor,
                'currency' => strtolower($payment->currency),
                'transfer_data' => ['destination' => $destinationAccountId],
                'metadata' => ['kongoinvestment_payment_id' => (string) $payment->id],
            ], ['Idempotency-Key' => $payment->idempotency_key]);

        if ($response->failed()) {
            throw new \RuntimeException('Stripe Connect payment_intents request failed: '.$response->body());
        }

        $intent = $response->json();
        $payment->update(['provider_reference' => $intent['id']]);

        return new PaymentIntentResult(providerReference: $intent['id'], clientSecret: $intent['client_secret'] ?? null);
    }

    // Status/refund/webhook handling share the exact same Stripe API
    // shape and signature scheme — delegate rather than duplicate.
    public function verifyPayment(Payment $payment): PaymentStatus
    {
        return $this->stripe->verifyPayment($payment);
    }

    public function getStatus(Payment $payment): PaymentStatus
    {
        return $this->stripe->getStatus($payment);
    }

    public function refund(Payment $payment, ?int $amountMinor = null): RefundResult
    {
        return $this->stripe->refund($payment, $amountMinor);
    }

    public function handleWebhook(Request $request): ?WebhookEvent
    {
        return $this->stripe->handleWebhook($request);
    }
}
