<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContractStatus;
use App\Enums\EscrowStatus;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Escrow;
use App\Models\MatchModel;
use App\Models\Payment;
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
     * remboursements, disputes — the pieces that exist today are shown
     * from their real tables, and the ones that don't are absent rather
     * than faked at zero. Escrow funds are never counted as revenue.
     */
    public function index(Request $request): Response
    {
        $revenueByCurrency = Payment::query()
            ->where('purpose', PaymentPurpose::Subscription->value)
            ->where('status', PaymentStatus::Succeeded->value)
            ->selectRaw('currency, sum(amount_minor) as total_minor')
            ->groupBy('currency')
            ->pluck('total_minor', 'currency')
            ->mapWithKeys(fn (mixed $total, string $currency) => [
                strtoupper($currency) => round(((int) $total) / 100, 2),
            ]);

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
                    'pending_review' => Project::where('status', 'pending_review')->count(),
                    'published' => Project::where('status', 'published')->count(),
                    'rejected' => Project::where('status', 'rejected')->count(),
                    'archived' => Project::where('status', 'archived')->count(),
                    'closed' => Project::where('status', 'closed')->count(),
                ],
                'totalMatches' => MatchModel::count(),
                'subscriptionsByPlan' => Subscription::query()
                    ->join('subscription_plans', 'plan_subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
                    ->where('plan_subscriptions.status', 'active')
                    ->selectRaw('subscription_plans.key as plan_key, count(*) as total')
                    ->groupBy('subscription_plans.key')
                    ->pluck('total', 'plan_key'),
                'investments' => [
                    'funded' => Escrow::query()
                        ->whereNotNull('funded_at')
                        ->whereNotIn('status', [
                            EscrowStatus::Refunded->value,
                            EscrowStatus::Cancelled->value,
                        ])
                        ->count(),
                    'held' => Escrow::query()
                        ->whereIn('status', [
                            EscrowStatus::Funded->value,
                            EscrowStatus::Held->value,
                            EscrowStatus::AwaitingRelease->value,
                            EscrowStatus::RefundRequested->value,
                            EscrowStatus::Disputed->value,
                        ])
                        ->count(),
                    'disputed' => Escrow::query()
                        ->where('status', EscrowStatus::Disputed->value)
                        ->count(),
                    'contractsAccepted' => Contract::query()
                        ->where('status', ContractStatus::Accepted->value)
                        ->count(),
                ],
                'revenue' => [
                    'succeededPayments' => Payment::query()
                        ->where('purpose', PaymentPurpose::Subscription->value)
                        ->where('status', PaymentStatus::Succeeded->value)
                        ->count(),
                    'byCurrency' => $revenueByCurrency,
                ],
                'canViewStatistics' => (bool) $request->user()?->can('statistics.view'),
            ],
        ]);
    }
}
