<?php

namespace App\Listeners;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Laravel\Cashier\Events\WebhookHandled;

/**
 * Fires after Cashier's own WebhookController has already synced its
 * subscriptions/subscription_items tables — this only mirrors that result
 * into the app's own plan_subscriptions table so SubscriptionService's
 * feature/limit gating (which knows nothing about Stripe) stays correct.
 */
class SyncPlanSubscriptionFromCashier
{
    public function __construct(private SubscriptionService $subscriptions) {}

    public function handle(WebhookHandled $event): void
    {
        $type = $event->payload['type'] ?? null;

        if (! in_array($type, ['customer.subscription.created', 'customer.subscription.updated'], true)) {
            return;
        }

        $stripeSubscription = $event->payload['data']['object'];
        $status = $stripeSubscription['status'] ?? null;

        if (! in_array($status, ['active', 'trialing'], true)) {
            return;
        }

        $stripePriceId = $stripeSubscription['items']['data'][0]['price']['id'] ?? null;
        $user = User::where('stripe_id', $stripeSubscription['customer'])->first();

        if (! $user || ! $stripePriceId) {
            return; // not one of our billed plans, or the customer couldn't be resolved
        }

        $plan = SubscriptionPlan::where('stripe_price_id', $stripePriceId)->first();
        $billingPeriod = 'monthly';

        if (! $plan) {
            $plan = SubscriptionPlan::where('annual_stripe_price_id', $stripePriceId)->first();
            $billingPeriod = 'annual';
        }

        if (! $plan) {
            return;
        }

        $this->subscriptions->syncPlanFromCashierSubscription($user, $plan, $stripeSubscription['id'], $billingPeriod);
    }
}
