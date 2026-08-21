<?php

use App\Services\Payments\Providers\StripePaymentProvider;
use Illuminate\Http\Request;

function stripeSignedRequest(array $payload, string $secret, ?int $timestamp = null): Request
{
    $timestamp ??= time();
    $body = json_encode($payload);
    $signedPayload = "{$timestamp}.{$body}";
    $signature = hash_hmac('sha256', $signedPayload, $secret);

    return Request::create('/webhooks/stripe', 'POST', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        'CONTENT_TYPE' => 'application/json',
    ], $body);
}

beforeEach(function () {
    config(['services.stripe.webhook_secret' => 'whsec_test_secret']);
});

it('accepts a webhook whose signature matches Stripe\'s documented HMAC scheme', function () {
    $payload = [
        'id' => 'evt_123',
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => ['id' => 'pi_123', 'status' => 'succeeded', 'amount' => 2900, 'currency' => 'usd']],
    ];

    $request = stripeSignedRequest($payload, 'whsec_test_secret');

    $event = (new StripePaymentProvider)->handleWebhook($request);

    expect($event)->not->toBeNull();
    expect($event->providerEventId)->toBe('evt_123');
    expect($event->providerReference)->toBe('pi_123');
    expect($event->status->value)->toBe('succeeded');
});

it('rejects a webhook signed with the wrong secret', function () {
    $payload = ['id' => 'evt_123', 'type' => 'payment_intent.succeeded', 'data' => ['object' => ['id' => 'pi_123']]];

    $request = stripeSignedRequest($payload, 'wrong_secret');

    $event = (new StripePaymentProvider)->handleWebhook($request);

    expect($event)->toBeNull();
});

it('rejects a webhook whose payload was tampered with after signing', function () {
    $payload = ['id' => 'evt_123', 'type' => 'payment_intent.succeeded', 'data' => ['object' => ['id' => 'pi_123', 'amount' => 2900]]];
    $request = stripeSignedRequest($payload, 'whsec_test_secret');

    // Simulate a MITM changing the amount after the signature was computed.
    $tamperedBody = str_replace('2900', '999999', $request->getContent());
    $tamperedRequest = Request::create('/webhooks/stripe', 'POST', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $request->header('Stripe-Signature'),
        'CONTENT_TYPE' => 'application/json',
    ], $tamperedBody);

    $event = (new StripePaymentProvider)->handleWebhook($tamperedRequest);

    expect($event)->toBeNull();
});

it('rejects a webhook signed too long ago (replay protection)', function () {
    $payload = ['id' => 'evt_123', 'type' => 'payment_intent.succeeded', 'data' => ['object' => ['id' => 'pi_123']]];
    $request = stripeSignedRequest($payload, 'whsec_test_secret', time() - 600); // 10 minutes old

    $event = (new StripePaymentProvider)->handleWebhook($request);

    expect($event)->toBeNull();
});
