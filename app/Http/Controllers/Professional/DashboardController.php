<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Almost everything a Professional actually does — Deal Room
     * participation, document access, Escrow/KYC/contract involvement
     * (spec §130) — depends on Phases 8-11, none of which exist yet.
     * This dashboard is honest about that rather than showing fake
     * activity: it's a landing spot with account basics only.
     */
    public function index(Request $request, SubscriptionService $subscriptions): Response
    {
        return Inertia::render('Dashboard/Professional', [
            'currentPlan' => $subscriptions->currentPlanFor($request->user())->only(['key', 'name']),
            'profileComplete' => (bool) $request->user()->professionalProfile?->onboarding_completed,
        ]);
    }
}
