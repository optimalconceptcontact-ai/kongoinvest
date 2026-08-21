<?php

use App\Enums\PaymentProviderName;
use App\Enums\PaymentPurpose;
use App\Models\Escrow;
use App\Models\Payment;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
    config(['services.stripe.webhook_secret' => 'whsec_test_secret']);
});

function postSignedStripeWebhook(array $payload): \Illuminate\Testing\TestResponse
{
    $timestamp = time();
    $body = json_encode($payload);
    $signature = hash_hmac('sha256', "{$timestamp}.{$body}", 'whsec_test_secret');

    return test()->withHeaders(['Stripe-Signature' => "t={$timestamp},v1={$signature}"])
        ->call('POST', '/webhooks/stripe', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body);
}

it('moves escrow from pending to held once a genuinely signed funding webhook arrives', function () {
    $escrow = Escrow::factory()->create(['status' => 'pending', 'amount_minor' => 50_000_00, 'currency' => 'USD']);

    $payment = Payment::create([
        'payer_id' => $escrow->investor_user_id,
        'purpose' => PaymentPurpose::Investment->value,
        'payable_type' => Escrow::class,
        'payable_id' => $escrow->id,
        'provider' => PaymentProviderName::Stripe->value,
        'provider_reference' => 'pi_escrow_test',
        'amount_minor' => $escrow->amount_minor,
        'currency' => $escrow->currency,
        'status' => 'pending',
        'idempotency_key' => (string) Str::uuid(),
    ]);

    postSignedStripeWebhook([
        'id' => 'evt_escrow_1',
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => ['id' => 'pi_escrow_test', 'status' => 'succeeded', 'amount' => 50_000_00, 'currency' => 'usd']],
    ])->assertOk();

    $escrow->refresh();
    expect($escrow->status->value)->toBe('held');
    expect($escrow->funded_at)->not->toBeNull();
    expect($escrow->events()->pluck('to_status')->all())->toContain('funded', 'held');
});

it('never double-funds an escrow if the funding webhook is redelivered', function () {
    $escrow = Escrow::factory()->create(['status' => 'pending', 'amount_minor' => 10_000_00, 'currency' => 'USD']);
    Payment::create([
        'payer_id' => $escrow->investor_user_id, 'purpose' => 'investment',
        'payable_type' => Escrow::class, 'payable_id' => $escrow->id,
        'provider' => 'stripe', 'provider_reference' => 'pi_redeliver',
        'amount_minor' => 10_000_00, 'currency' => 'USD', 'status' => 'pending',
        'idempotency_key' => (string) Str::uuid(),
    ]);

    $payload = [
        'id' => 'evt_redeliver_1',
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => ['id' => 'pi_redeliver', 'status' => 'succeeded', 'amount' => 10_000_00, 'currency' => 'usd']],
    ];

    postSignedStripeWebhook($payload)->assertOk();
    postSignedStripeWebhook($payload)->assertOk();

    expect($escrow->fresh()->events()->count())->toBe(2); // funded + held, not 4
});
