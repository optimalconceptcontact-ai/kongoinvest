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

/**
 * Unlike Stripe and PayPal, Orange Money, M-Pesa, and Airtel Money don't
 * share one universal public API — each operator (or the aggregator
 * fronting them) issues merchant-specific credentials, base URLs, and
 * webhook conventions under a signed merchant agreement, and those
 * details aren't finalized until that contract exists. This class
 * implements the *shape* every operator broadly follows (initiate a
 * mobile-money charge to a phone number, receive an async
 * shared-secret-HMAC-signed notification) so a real integration is a
 * config change away, but the concrete endpoint paths below are
 * illustrative, not verified against any operator's current live docs
 * (spec is honest about this rather than presenting a guess as fact).
 */
abstract class AbstractMobileMoneyProvider implements PaymentProviderInterface
{
    abstract protected function configPrefix(): string; // e.g. 'orange_money'

    abstract protected function baseUrl(): string;

    public function createPayment(Payment $payment): PaymentIntentResult
    {
        $this->assertConfigured();

        $phoneNumber = $payment->metadata['phone_number'] ?? null;

        if (! $phoneNumber) {
            throw new \InvalidArgumentException('Mobile Money payments require metadata.phone_number.');
        }

        $response = Http::withToken($this->credential('api_key'))
            ->post($this->baseUrl().'/payments/initiate', [
                'amount' => $payment->amount_minor / 100, // most Mobile Money APIs bill in whole units, not minor
                'currency' => $payment->currency,
                'phone_number' => $phoneNumber,
                'external_reference' => $payment->idempotency_key,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException(static::class.' payment initiation failed: '.$response->body());
        }

        $reference = $response->json('transaction_id') ?? $payment->idempotency_key;
        $payment->update(['provider_reference' => $reference]);

        return new PaymentIntentResult(providerReference: $reference);
    }

    public function verifyPayment(Payment $payment): PaymentStatus
    {
        return $this->getStatus($payment);
    }

    public function getStatus(Payment $payment): PaymentStatus
    {
        $response = Http::withToken($this->credential('api_key'))
            ->get($this->baseUrl()."/payments/{$payment->provider_reference}/status");

        if ($response->failed()) {
            return $payment->status;
        }

        return $this->mapStatus($response->json('status'));
    }

    public function refund(Payment $payment, ?int $amountMinor = null): RefundResult
    {
        return new RefundResult(successful: false, failureReason: 'Refunds must be confirmed against the specific operator\'s merchant agreement before implementation.');
    }

    /**
     * Generic shared-secret HMAC verification — the common baseline
     * across most Mobile Money notification schemes, but the exact
     * header name and payload-to-sign format vary per operator and must
     * be confirmed against that operator's real documentation before
     * going live. Never trust an unsigned notification regardless.
     */
    public function handleWebhook(Request $request): ?WebhookEvent
    {
        $secret = $this->credential('webhook_secret');
        $signature = $request->header('X-Signature', '');

        if (blank($secret) || blank($signature)) {
            return null;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expected, $signature)) {
            Log::warning(static::class.' webhook rejected: signature mismatch');

            return null;
        }

        $payload = $request->json()->all();

        return new WebhookEvent(
            providerEventId: $payload['event_id'] ?? $payload['transaction_id'] ?? '',
            providerReference: $payload['transaction_id'] ?? '',
            status: $this->mapStatus($payload['status'] ?? null),
            type: $payload['event_type'] ?? 'payment.notification',
            amountMinor: isset($payload['amount']) ? (int) round((float) $payload['amount'] * 100) : null,
            currency: $payload['currency'] ?? null,
            rawPayload: $payload,
        );
    }

    protected function mapStatus(?string $status): PaymentStatus
    {
        return match (strtoupper((string) $status)) {
            'SUCCESS', 'SUCCESSFUL', 'COMPLETED' => PaymentStatus::Succeeded,
            'FAILED', 'DECLINED' => PaymentStatus::Failed,
            'PENDING', 'INITIATED' => PaymentStatus::Pending,
            'CANCELLED' => PaymentStatus::Cancelled,
            default => PaymentStatus::Processing,
        };
    }

    private function credential(string $key): ?string
    {
        return config("services.{$this->configPrefix()}.{$key}");
    }

    private function assertConfigured(): void
    {
        if (blank($this->credential('api_key'))) {
            throw new \RuntimeException(static::class.' is not configured.');
        }
    }
}
