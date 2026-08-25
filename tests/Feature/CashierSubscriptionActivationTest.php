<?php

use App\Listeners\SyncPlanSubscriptionFromCashier;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Laravel\Cashier\Events\WebhookHandled;

/**
 * The subscribe()/IncompletePayment cases below hit the real Stripe test
 * API (Cashier ships no fake() helper) and need STRIPE_SECRET and a real
 * Stripe Price on the Entrepreneur plan (subscription_plans.stripe_price_id)
 * to run — see the plan's "manual steps" section. They skip cleanly
 * without those, so this file doesn't fail the suite in an environment
 * that hasn't been wired up to Stripe yet.
 */
beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

function skipWithoutStripeTestSetup(): void
{
    if (! config('services.stripe.secret') || ! SubscriptionPlan::where('key', 'entrepreneur')->value('stripe_price_id')) {
        test()->markTestSkipped('Requires STRIPE_SECRET and subscription_plans.stripe_price_id to be configured.');
    }
}

it('creates a plan_subscriptions row once a Cashier subscription is created with a valid card', function () {
    skipWithoutStripeTestSetup();

    $entrepreneur = User::factory()->entrepreneur()->create();
    app(SubscriptionService::class)->assignFreePlan($entrepreneur);
    $plan = SubscriptionPlan::where('key', 'entrepreneur')->firstOrFail();

    $entrepreneur->newSubscription('default', $plan->stripe_price_id)->create('pm_card_visa');

    expect($entrepreneur->subscribed('default'))->toBeTrue();

    app(SubscriptionService::class)->syncPlanFromCashierSubscription(
        $entrepreneur, $plan, $entrepreneur->subscription('default')->stripe_id,
    );

    expect(app(SubscriptionService::class)->currentPlanFor($entrepreneur->fresh())->key)->toBe('entrepreneur');
});

it('surfaces IncompletePayment for a card requiring 3DS instead of silently activating', function () {
    skipWithoutStripeTestSetup();

    $entrepreneur = User::factory()->entrepreneur()->create();
    app(SubscriptionService::class)->assignFreePlan($entrepreneur);
    $plan = SubscriptionPlan::where('key', 'entrepreneur')->firstOrFail();

    try {
        $entrepreneur->newSubscription('default', $plan->stripe_price_id)->create('pm_card_threeDSecure2Required');
        expect(false)->toBeTrue('Expected IncompletePayment to be thrown.');
    } catch (\Laravel\Cashier\Exceptions\IncompletePayment) {
        expect($entrepreneur->subscription('default')->incomplete())->toBeTrue();
    }
});

it('mirrors a Cashier subscription into plan_subscriptions when a webhook is handled', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    app(SubscriptionService::class)->assignFreePlan($entrepreneur);
    $entrepreneur->forceFill(['stripe_id' => 'cus_fake_123'])->save();
    $plan = SubscriptionPlan::where('key', 'entrepreneur')->firstOrFail();
    $plan->update(['stripe_price_id' => 'price_fake_entrepreneur']);

    $event = new WebhookHandled([
        'id' => 'evt_fake_1',
        'type' => 'customer.subscription.created',
        'data' => ['object' => [
            'id' => 'sub_fake_123',
            'customer' => 'cus_fake_123',
            'status' => 'active',
            'items' => ['data' => [['price' => ['id' => 'price_fake_entrepreneur']]]],
        ]],
    ]);

    app(SyncPlanSubscriptionFromCashier::class)->handle($event);

    expect(app(SubscriptionService::class)->currentPlanFor($entrepreneur->fresh())->key)->toBe('entrepreneur');
});
