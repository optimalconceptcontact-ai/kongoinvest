<?php

use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

it('never lets a user view another user\'s payment', function () {
    $owner = User::factory()->entrepreneur()->create();
    $stranger = User::factory()->investor()->create();
    $payment = Payment::factory()->create(['payer_id' => $owner->id]);

    expect($owner->can('view', $payment))->toBeTrue();
    expect($stranger->can('view', $payment))->toBeFalse();
});

it('fails cleanly with a clear exception when a provider has no API key configured', function () {
    config(['services.stripe.secret' => null]);

    $payment = Payment::factory()->create(['provider' => 'stripe']);

    expect(fn () => (new \App\Services\Payments\Providers\StripePaymentProvider)->createPayment($payment))
        ->toThrow(\RuntimeException::class, 'Stripe is not configured');
});

it('never lets checkout be reached for the Free plan', function () {
    $user = User::factory()->entrepreneur()->create();
    $free = \App\Models\SubscriptionPlan::where('key', 'free')->firstOrFail();

    $this->actingAs($user)
        ->get("/settings/subscription/{$free->id}/checkout")
        ->assertNotFound();
});
