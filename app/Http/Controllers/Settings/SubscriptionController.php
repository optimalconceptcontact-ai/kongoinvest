<?php

namespace App\Http\Controllers\Settings;

use App\Data\SubscriptionPlanData;
use App\Http\Controllers\Controller;
use App\Http\Requests\SwitchSubscriptionPlanRequest;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function edit(Request $request, SubscriptionService $subscriptions): Response
    {
        $user = $request->user();
        $current = $subscriptions->currentPlanFor($user);

        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('order')
            ->get()
            ->map(fn (SubscriptionPlan $plan) => SubscriptionPlanData::fromModel($plan, $user, isCurrent: $plan->id === $current->id));

        return Inertia::render('Settings/Subscription', [
            'plans' => $plans,
            'currentPlanKey' => $current->key,
        ]);
    }

    public function update(SwitchSubscriptionPlanRequest $request, SubscriptionService $subscriptions): RedirectResponse
    {
        $plan = SubscriptionPlan::where('key', $request->validated('plan'))->firstOrFail();

        // Phase 10: paid plans now genuinely require a completed payment
        // — switchPlan() is called by ActivateSubscriptionOnPayment once
        // Payment succeeds, not from here. Free stays instant, since
        // there's nothing to charge.
        if ($plan->price_minor > 0) {
            // 303, not the default 302: this redirect follows a PUT, and
            // only a 303 forces the browser to replay it as a GET against
            // billing.checkout (a GET-only route). A 302 here silently
            // fails — the client resubmits PUT and 405s — which is why
            // choosing a paid plan never reached the checkout page.
            return redirect()->route('settings.billing.checkout', $plan, 303);
        }

        $subscriptions->switchPlan($request->user(), $plan);

        return back()->with('success', "Vous êtes maintenant sur le plan {$plan->name}.");
    }
}
