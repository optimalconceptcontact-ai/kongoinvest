<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;

/**
 * Handles the PayPal/Mobile Money subscription rail — the one-off
 * CreatePayment flow BillingController::pay() uses, since those providers
 * can't go through Cashier. Card subscriptions activate via
 * SyncPlanSubscriptionFromCashier instead, triggered by Cashier's own
 * webhook, not this listener.
 */
class ActivateSubscriptionOnPayment
{
    public function __construct(private SubscriptionService $subscriptions) {}

    public function handle(PaymentSucceeded $event): void
    {
        $payment = $event->payment;

        if ($payment->purpose->value !== 'subscription') {
            return;
        }

        $plan = $payment->payable;

        if (! $plan instanceof SubscriptionPlan) {
            return;
        }

        $this->subscriptions->switchPlan($payment->payer, $plan, $payment->metadata['billing_period'] ?? 'monthly');
    }
}
