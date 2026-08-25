<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionHistoryController extends Controller
{
    /** Always scoped to the authenticated user — never a query param the client could tamper with (spec §100 pattern). */
    public function index(Request $request): Response
    {
        $subscriptions = $request->user()->planSubscriptions()
            ->with('plan')
            ->latest('starts_at')
            ->get()
            ->map(fn (Subscription $s) => $this->summarize($s));

        return Inertia::render('Settings/Subscription/History', [
            'subscriptions' => $subscriptions,
        ]);
    }

    public function show(Request $request, Subscription $subscription): Response
    {
        $this->authorize('view', $subscription);

        $subscription->load('plan', 'user');

        return Inertia::render('Settings/Subscription/Invoice', [
            'subscription' => [
                ...$this->summarize($subscription),
                'planKey' => $subscription->plan->key,
                'canceledAt' => $subscription->canceled_at?->toIso8601String(),
                'providerSubscriptionId' => $subscription->provider_subscription_id,
            ],
            'customer' => [
                'name' => $subscription->user->name,
                'email' => $subscription->user->email,
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function summarize(Subscription $subscription): array
    {
        return [
            'id' => $subscription->id,
            'planName' => $subscription->plan->name,
            'billingPeriod' => $subscription->billing_period,
            'amountMinor' => $subscription->plan->priceMinorFor($subscription->billing_period) ?? 0,
            'currency' => $subscription->plan->currency,
            'status' => $subscription->status->value,
            'statusLabel' => $subscription->status->label(),
            'startsAt' => $subscription->starts_at?->toIso8601String(),
            'endsAt' => $subscription->ends_at?->toIso8601String(),
            'paymentProvider' => $subscription->payment_provider,
        ];
    }
}
