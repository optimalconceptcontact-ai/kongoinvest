<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Only the stats that correspond to something actually built.
     * Spec §27 also lists revenus, paiements, fonds libérés,
     * remboursements, disputes — all Phase 10/11 territory (real money
     * moving). Rather than show a $0 "revenue" figure that looks like a
     * real metric but isn't, those rows are simply absent with a note,
     * not faked at zero.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Dashboard/Admin', [
            'stats' => [
                'usersByRole' => [
                    'entrepreneur' => User::role('entrepreneur')->count(),
                    'investor' => User::role('investor')->count(),
                    'admin' => User::role('admin')->count(),
                    // No 'professional' role count — Professional is a
                    // subscription plan, not a role (corrected; see
                    // 'subscriptionsByPlan' below for how many users are
                    // actually on that plan, which is the meaningful
                    // number here).
                ],
                'projectsByStatus' => [
                    'draft' => Project::where('status', 'draft')->count(),
                    'published' => Project::where('status', 'published')->count(),
                    'archived' => Project::where('status', 'archived')->count(),
                ],
                'totalMatches' => MatchModel::count(),
                'subscriptionsByPlan' => Subscription::query()
                    ->join('subscription_plans', 'plan_subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
                    ->where('plan_subscriptions.status', 'active')
                    ->selectRaw('subscription_plans.key as plan_key, count(*) as total')
                    ->groupBy('subscription_plans.key')
                    ->pluck('total', 'plan_key'),
            ],
        ]);
    }
}
