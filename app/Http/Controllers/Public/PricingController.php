<?php

namespace App\Http\Controllers\Public;

use App\Data\SubscriptionPlanData;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PricingController extends Controller
{
    public function index(Request $request): Response
    {
        $viewer = $request->user();

        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('order')
            ->get()
            ->map(fn (SubscriptionPlan $plan) => SubscriptionPlanData::fromModel($plan, $viewer));

        return Inertia::render('Public/Pricing', ['plans' => $plans]);
    }
}
