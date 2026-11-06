<?php

namespace App\Http\Controllers\Public;

use App\Data\SubscriptionPlanData;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PricingController extends Controller
{
    public function index(Request $request, SubscriptionService $subscriptions): Response
    {
        $viewer = $request->user();
        $currentId = $viewer ? $subscriptions->currentPlanFor($viewer)->id : null;

        return Inertia::render('Public/Pricing', [
            'plans' => Inertia::defer(fn () => SubscriptionPlan::where('is_active', true)
                ->orderBy('order')
                ->get()
                ->map(fn (SubscriptionPlan $plan) => SubscriptionPlanData::fromModel($plan, $viewer, isCurrent: $currentId !== null && $plan->id === $currentId))),
        ]);
    }
}
