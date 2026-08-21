<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Entrepreneur\DashboardController as EntrepreneurDashboard;
use App\Http\Controllers\Investor\DashboardController as InvestorDashboard;
use Illuminate\Http\Request;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * One URL (/dashboard, spec §106), rendered differently per role.
     * Each role's actual dashboard logic lives in its own controller —
     * this only dispatches, it doesn't know how to build any of them.
     *
     * Exactly three roles exist (admin, entrepreneur, investor) — there
     * is no separate "Professional" dashboard, because Professional is
     * a subscription plan (see SubscriptionPlanKey), not a role. An
     * entrepreneur or investor on the Professional plan still lands on
     * their normal role dashboard, just with that plan's features
     * unlocked.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return match (true) {
            $user->isAdmin() => app()->call([app(AdminDashboard::class), 'index']),
            $user->isEntrepreneur() => app()->call([app(EntrepreneurDashboard::class), 'index']),
            $user->isInvestor() => app()->call([app(InvestorDashboard::class), 'index']),
            default => abort(403),
        };
    }
}
