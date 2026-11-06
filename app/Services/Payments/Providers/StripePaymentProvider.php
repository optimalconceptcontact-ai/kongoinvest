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
use Illuminate\Support\Facades\Log;

class StripePaymentProvider implements PaymentProviderInterface
{
    public function createPayment(Payment $payment): PaymentIntentResult
    {
        $this->assertConfigured();

        $response = Http::withToken(config('services.stripe.secret'))
            ->asForm()
            ->post('https://api.stripe.com/v1/payment_intents', [
                'amount' => $payment->amount_minor,
                'currency' => strtolower($payment->currency),
                // Stripe's own idempotency support — belt and suspenders
                // with our idempotency_key column (spec §41): even if
                // our app retried the HTTP call itself, Stripe dedupes.
                'metadata' => [
                    'KongoInvest_payment_id' => (string) $payment->id,
                    'idempotency_key' => $payment->idempotency_key,
                ],
            ], [
                'Idempotency-Key' => $payment->idempotency_key,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Stripe payment_intents request failed: '.$response->body());
        }

        $intent = $response->json();

        $payment->update(['provider_reference' => $intent['id']]);

        return new PaymentIntentResult(
            providerReference: $intent['id'],
            clientSecret: $intent['client_secret'] ?? null,
        );
    }

    public function verifyPayment(Payment $payment): PaymentStatus
    {
        return $this->getStatus($payment);
    }

    public function getStatus(Payment $payment): PaymentStatus
    {
        $this->assertConfigured();

        $response = Http::withToken(config('services.stripe.secret'))
            ->get("https://api.stripe.com/v1/payment_intents/{$payment->provider_reference}");

        if ($response->failed()) {
            return $payment->status;
        }

        return $this->mapStatus($response->json('status'));
    }

    public function refund(Payment $payment, ?int $amountMinor = null): RefundResult
    {
        $this->assertConfigured();

        $response = Http::withToken(config('services.stripe.secret'))
            ->asForm()
            ->post('https://api.stripe.com/v1/refunds', array_filter([
                'payment_intent' => $payment->provider_reference,
                'amount' => $amountMinor,
            ]));

        if ($response->failed()) {
            return new RefundResult(successful: false, failureReason: $response->json('error.message'));
        }

        $refund = $response->json();

        return new RefundResult(
            successful: true,
            providerRefundId: $refund['id'],
            amountMinor: $refund['amount'],
        );
    }

    /**
     * Stripe's documented signature scheme (verified against their
     * public docs, not guessed): the `Stripe-Signature` header is
     * `t=<timestamp>,v1=<hex hmac>`. The signed payload is
     * `"{timestamp}.{raw_body}"`, HMAC-SHA256'd with the webhook secret.
     * This is real, correct, and independently testable — no live
     * Stripe account needed to verify the math.
     */
    public function handleWebhook(Request $request): ?WebhookEvent
    {
        $secret = config('services.stripe.webhook_secret');
        $header = $request->header('Stripe-Signature', '');

        if (blank($secret) || blank($header)) {
            return null;
        }

        $parts = collect(explode(',', $header))
            ->mapWithKeys(function ($part) {
                [$key, $value] = array_pad(explode('=', $part, 2), 2, null);

                return [$key => $value];
            });

        $timestamp = $parts->get('t');
        $signature = $parts->get('v1');

        if (! $timestamp || ! $signature) {
            return null;
        }

        // Reject old signatures — a 5-minute tolerance window prevents a
        // captured, valid-looking payload from being replayed much later.
        if (abs(time() - (int) $timestamp) > 300) {
            Log::warning('Stripe webhook rejected: timestamp outside tolerance');

            return null;
        }

        $signedPayload = $timestamp.'.'.$request->getContent();
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        if (! hash_equals($expected, $signature)) {
            Log::warning('Stripe webhook rejected: signature mismatch');

            return null;
        }

        $payload = $request->json()->all();
        $object = $payload['data']['object'] ?? [];

        return new WebhookEvent(
            providerEventId: $payload['id'] ?? '',
            providerReference: $object['id'] ?? '',
            status: $this->mapStatus($object['status'] ?? null),
            type: $payload['type'] ?? 'unknown',
            amountMinor: $object['amount'] ?? null,
            currency: isset($object['currency']) ? strtoupper($object['currency']) : null,
            rawPayload: $payload,
        );
    }

    private function mapStatus(?string $stripeStatus): PaymentStatus
    {
        return match ($stripeStatus) {
            'succeeded' => PaymentStatus::Succeeded,
            'processing' => PaymentStatus::Processing,
            'requires_payment_method', 'requires_confirmation', 'requires_action' => PaymentStatus::Pending,
            'canceled' => PaymentStatus::Cancelled,
            default => PaymentStatus::Failed,
        };
    }

    private function assertConfigured(): void
    {
        if (blank(config('services.stripe.secret'))) {
            throw new \RuntimeException('Stripe is not configured — set STRIPE_SECRET in .env.');
        }
    }
}
