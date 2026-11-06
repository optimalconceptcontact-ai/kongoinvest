<?php

namespace App\Listeners;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\SubscriptionNotification;
use App\Services\SubscriptionService;
use Carbon\Carbon;
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

        if ($type === 'customer.subscription.deleted') {
            $this->cancelMirroredSubscription($event);

            return;
        }

        if ($type !== 'customer.subscription.created' && $type !== 'customer.subscription.updated') {
            return;
        }

        $stripeSubscription = $event->payload['data']['object'] ?? [];
        $status = $stripeSubscription['status'] ?? null;

        // Terminal states end the mirrored row exactly like a deletion.
        // Only `updated` carries them (`created` always starts alive).
        if ($type === 'customer.subscription.updated' && in_array($status, ['canceled', 'incomplete_expired'], true)) {
            $this->cancelMirroredSubscription($event);

            return;
        }

        // Dunning states: keep the row but flag it PastDue so feature
        // gating (which only counts active/trialing rows) falls back to
        // Free until Stripe recovers. Recovery arrives as updated→active
        // and is handled by the sync below.
        if (in_array($status, ['past_due', 'unpaid'], true)) {
            $this->flagMirroredSubscriptionPastDue($event);

            return;
        }

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

        $trialEndsAt = isset($stripeSubscription['trial_end'])
            ? Carbon::createFromTimestamp($stripeSubscription['trial_end'])
            : null;

        $this->subscriptions->syncPlanFromCashierSubscription($user, $plan, $stripeSubscription['id'], $billingPeriod, $trialEndsAt);
    }

    /**
     * Mirrors a Stripe-side cancellation locally: the mirrored
     * plan_subscriptions row ends immediately (no refund, same as a
     * self-service downgrade) and the user is notified. Feature gating
     * then falls back to Free via currentPlanFor() until they subscribe
     * again — no new Free row is written.
     */
    private function cancelMirroredSubscription(WebhookHandled $event): void
    {
        $stripeSubscription = $event->payload['data']['object'] ?? [];
        $user = User::where('stripe_id', $stripeSubscription['customer'] ?? null)->first();

        if (! $user || ! isset($stripeSubscription['id'])) {
            return;
        }

        $sub = Subscription::query()
            ->where('user_id', $user->id)
            ->where('provider_subscription_id', $stripeSubscription['id'])
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value, SubscriptionStatus::PastDue->value])
            ->first();

        if (! $sub) {
            return;
        }

        $sub->forceFill([
            'status' => SubscriptionStatus::Canceled->value,
            'ends_at' => now(),
            'canceled_at' => now(),
        ])->save();

        $user->notify(new SubscriptionNotification($sub->fresh('plan'), 'canceled'));
    }

    /**
     * Mirrors Stripe dunning locally: the row stays (same subscription,
     * still retrying on Stripe's side) but flips to PastDue, which drops
     * feature gating to Free until recovery. Notifies once per transition
     * — repeated webhooks while already PastDue stay silent.
     */
    private function flagMirroredSubscriptionPastDue(WebhookHandled $event): void
    {
        $stripeSubscription = $event->payload['data']['object'] ?? [];
        $user = User::where('stripe_id', $stripeSubscription['customer'] ?? null)->first();

        if (! $user || ! isset($stripeSubscription['id'])) {
            return;
        }

        $sub = Subscription::query()
            ->where('user_id', $user->id)
            ->where('provider_subscription_id', $stripeSubscription['id'])
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Trialing->value])
            ->first();

        if (! $sub) {
            return;
        }

        $sub->forceFill(['status' => SubscriptionStatus::PastDue->value])->save();

        $user->notify(new SubscriptionNotification($sub->fresh('plan'), 'payment_failed'));
    }
}
