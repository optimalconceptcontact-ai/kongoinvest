<?php

namespace App\Http\Controllers\Investor;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use App\Models\Project;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, SubscriptionService $subscriptions): Response
    {
        $user = $request->user();
        $investorProfile = $user->investorProfile;

        $topMatches = $investorProfile
            ? MatchModel::where('investor_profile_id', $investorProfile->id)
                ->orderByDesc('score')
                ->take(5)
                ->with('project')
                ->get()
                ->map(fn (MatchModel $m) => [
                    'id' => $m->id,
                    'score' => $m->score,
                    'status' => $m->status->value,
                    'projectTitle' => $m->project->title,
                    'projectSlug' => $m->project->slug,
                ])
            : collect();

        return Inertia::render('Dashboard/Investor', [
            'matchCount' => $investorProfile ? MatchModel::where('investor_profile_id', $investorProfile->id)->count() : 0,
            'pendingMatchCount' => $investorProfile ? MatchModel::where('investor_profile_id', $investorProfile->id)->where('status', 'pending')->count() : 0,
            'topMatches' => $topMatches,
            'profileComplete' => (bool) $investorProfile?->onboarding_completed,
            'publishedProjectCount' => Project::published()->count(),
            'currentPlan' => $subscriptions->currentPlanFor($user)->only(['key', 'name']),
        ]);
    }
}
