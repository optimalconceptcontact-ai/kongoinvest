<?php

namespace App\Http\Controllers\Entrepreneur;

use App\Data\ProjectData;
use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use App\Services\ProjectLimitService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, ProjectLimitService $limits, SubscriptionService $subscriptions): Response
    {
        $user = $request->user();

        // §25: "afficher: projet actif, score, investisseurs intéressés,
        // matches, ..." — the active project is whichever one isn't
        // archived, same status set ProjectLimitService counts as
        // "active" (spec §20).
        $activeProject = $user->projects()
            ->whereIn('status', ['draft', 'published'])
            ->with(['sectors', 'stages'])
            ->latest()
            ->first();

        $matchCount = $activeProject
            ? MatchModel::where('project_id', $activeProject->id)->count()
            : 0;

        $topMatches = $activeProject
            ? MatchModel::where('project_id', $activeProject->id)
                ->orderByDesc('score')
                ->take(3)
                ->with('investorProfile.user')
                ->get()
                ->map(fn (MatchModel $m) => [
                    'id' => $m->id,
                    'score' => $m->score,
                    'investorName' => $m->investorProfile->user->name,
                ])
            : [];

        return Inertia::render('Dashboard/Entrepreneur', [
            'activeProject' => $activeProject ? ProjectData::fromModel($activeProject) : null,
            'canCreateProject' => $limits->canCreateProject($user),
            'matchCount' => $matchCount,
            'topMatches' => $topMatches,
            'currentPlan' => $subscriptions->currentPlanFor($user)->only(['key', 'name']),
        ]);
    }
}
