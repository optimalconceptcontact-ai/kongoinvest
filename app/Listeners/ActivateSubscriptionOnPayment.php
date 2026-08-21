<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;

class ActivateSubscriptionOnPayment
{
    public function __construct(private SubscriptionService $subscriptions) {}

    public function handle(PaymentSucceeded $event): void
    {
        $payment = $event->payment;

        if ($payment->purpose->value !== 'subscription') {
            return; // Phase 11's Escrow will handle purpose=investment itself
        }

        $plan = $payment->payable;

        if (! $plan instanceof SubscriptionPlan) {
            return;
        }

        $this->subscriptions->switchPlan($payment->payer, $plan);
    }
}
