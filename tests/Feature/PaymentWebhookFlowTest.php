<?php

use App\Models\Payment;
use App\Models\PaymentTransaction;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
    config(['services.stripe.webhook_secret' => 'whsec_test_secret']);
});

function postStripeWebhook(array $payload, ?int $timestamp = null): \Illuminate\Testing\TestResponse
{
    $timestamp ??= time();
    $body = json_encode($payload);
    $signature = hash_hmac('sha256', "{$timestamp}.{$body}", 'whsec_test_secret');

    return test()->withHeaders(['Stripe-Signature' => "t={$timestamp},v1={$signature}"])
        ->call('POST', '/webhooks/stripe', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body);
}

it('never processes the same webhook event twice, even if the provider redelivers it', function () {
    $payment = Payment::factory()->create(['provider_reference' => 'pi_dup_test', 'amount_minor' => 2900]);

    $eventPayload = [
        'id' => 'evt_dup_1',
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => ['id' => 'pi_dup_test', 'status' => 'succeeded', 'amount' => 2900, 'currency' => 'usd']],
    ];

    postStripeWebhook($eventPayload)->assertOk();
    postStripeWebhook($eventPayload)->assertOk(); // redelivered — same event ID

    expect(PaymentTransaction::where('payment_id', $payment->id)->count())->toBe(1);
});

it('never trusts a webhook claiming a different amount than what was charged', function () {
    $payment = Payment::factory()->create(['provider_reference' => 'pi_mismatch', 'amount_minor' => 2900, 'status' => 'pending']);

    postStripeWebhook([
        'id' => 'evt_mismatch_1',
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => ['id' => 'pi_mismatch', 'status' => 'succeeded', 'amount' => 999999, 'currency' => 'usd']],
    ])->assertOk();

    // The webhook was structurally valid (real signature) but the
    // amount didn't match — the payment must NOT have been marked
    // succeeded on the strength of a mismatched claim.
    expect($payment->fresh()->status->value)->toBe('pending');
});

it('rejects a webhook with an invalid signature at the HTTP layer, before any processing', function () {
    $this->withHeaders(['Stripe-Signature' => 't='.time().',v1=not_a_real_signature'])
        ->call('POST', '/webhooks/stripe', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['id' => 'evt_fake']))
        ->assertStatus(400);
});
