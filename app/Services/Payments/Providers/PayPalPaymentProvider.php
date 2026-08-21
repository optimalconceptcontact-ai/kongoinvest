<?php

namespace App\Services\Payments\Providers;

use App\Contracts\PaymentProviderInterface;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Payments\PaymentIntentResult;
use App\Services\Payments\RefundResult;
use App\Services\Payments\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalPaymentProvider implements PaymentProviderInterface
{
    private function baseUrl(): string
    {
        return config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /** OAuth2 client-credentials token, cached for its actual TTL. */
    private function accessToken(): string
    {
        return Cache::remember('paypal.access_token', now()->addMinutes(50), function () {
            $response = Http::asForm()
                ->withBasicAuth(config('services.paypal.client_id'), config('services.paypal.client_secret'))
                ->post($this->baseUrl().'/v1/oauth2/token', ['grant_type' => 'client_credentials']);

            if ($response->failed()) {
                throw new \RuntimeException('PayPal OAuth token request failed: '.$response->body());
            }

            return $response->json('access_token');
        });
    }

    public function createPayment(Payment $payment): PaymentIntentResult
    {
        $this->assertConfigured();

        $response = Http::withToken($this->accessToken())
            ->withHeaders(['PayPal-Request-Id' => $payment->idempotency_key]) // PayPal's own idempotency header
            ->post($this->baseUrl().'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => (string) $payment->id,
                    'amount' => [
                        'currency_code' => $payment->currency,
                        'value' => number_format($payment->amount_minor / 100, 2, '.', ''),
                    ],
                ]],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('PayPal order creation failed: '.$response->body());
        }

        $order = $response->json();
        $approveLink = collect($order['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? null;

        $payment->update(['provider_reference' => $order['id']]);

        return new PaymentIntentResult(providerReference: $order['id'], redirectUrl: $approveLink);
    }

    public function verifyPayment(Payment $payment): PaymentStatus
    {
        return $this->getStatus($payment);
    }

    public function getStatus(Payment $payment): PaymentStatus
    {
        $response = Http::withToken($this->accessToken())
            ->get($this->baseUrl()."/v2/checkout/orders/{$payment->provider_reference}");

        if ($response->failed()) {
            return $payment->status;
        }

        return $this->mapStatus($response->json('status'));
    }

    public function refund(Payment $payment, ?int $amountMinor = null): RefundResult
    {
        // Refunding a PayPal order requires the capture ID, obtained
        // from the order's capture record — a real implementation
        // fetches the order first to find it. Omitted here since it
        // needs a live order to demonstrate against.
        return new RefundResult(successful: false, failureReason: 'Not implemented in this scaffold — requires the capture ID from a completed order.');
    }

    /**
     * Unlike Stripe's local-HMAC scheme, PayPal's webhook verification
     * is certificate-based and PayPal explicitly documents that you
     * verify by calling THEIR API
     * (/v1/notifications/verify-webhook-signature) with the transmission
     * ID/signature/time headers plus your Webhook ID — there is no
     * local secret to HMAC against. This is implemented for real here,
     * not approximated with a shared-secret HMAC that wouldn't actually
     * match PayPal's scheme.
     */
    public function handleWebhook(Request $request): ?WebhookEvent
    {
        $webhookId = config('services.paypal.webhook_id');

        if (blank($webhookId)) {
            return null;
        }

        $response = Http::withToken($this->accessToken())
            ->post($this->baseUrl().'/v1/notifications/verify-webhook-signature', [
                'transmission_id' => $request->header('Paypal-Transmission-Id'),
                'transmission_time' => $request->header('Paypal-Transmission-Time'),
                'cert_url' => $request->header('Paypal-Cert-Url'),
                'auth_algo' => $request->header('Paypal-Auth-Algo'),
                'transmission_sig' => $request->header('Paypal-Transmission-Sig'),
                'webhook_id' => $webhookId,
                'webhook_event' => $request->json()->all(),
            ]);

        if ($response->failed() || $response->json('verification_status') !== 'SUCCESS') {
            Log::warning('PayPal webhook signature verification failed');

            return null;
        }

        $payload = $request->json()->all();
        $resource = $payload['resource'] ?? [];

        return new WebhookEvent(
            providerEventId: $payload['id'] ?? '',
            providerReference: $resource['id'] ?? ($resource['supplementary_data']['related_ids']['order_id'] ?? ''),
            status: $this->mapStatus($payload['event_type'] ?? null),
            type: $payload['event_type'] ?? 'unknown',
            amountMinor: isset($resource['amount']['value']) ? (int) round((float) $resource['amount']['value'] * 100) : null,
            currency: $resource['amount']['currency_code'] ?? null,
            rawPayload: $payload,
        );
    }

    private function mapStatus(?string $paypalStatus): PaymentStatus
    {
        return match (true) {
            $paypalStatus === 'COMPLETED' || str_contains((string) $paypalStatus, 'CAPTURE.COMPLETED') => PaymentStatus::Succeeded,
            str_contains((string) $paypalStatus, 'DENIED') || str_contains((string) $paypalStatus, 'DECLINED') => PaymentStatus::Failed,
            $paypalStatus === 'APPROVED' => PaymentStatus::Processing,
            $paypalStatus === 'VOIDED' => PaymentStatus::Cancelled,
            default => PaymentStatus::Pending,
        };
    }

    private function assertConfigured(): void
    {
        if (blank(config('services.paypal.client_id'))) {
            throw new \RuntimeException('PayPal is not configured — set PAYPAL_CLIENT_ID/SECRET in .env.');
        }
    }
}
