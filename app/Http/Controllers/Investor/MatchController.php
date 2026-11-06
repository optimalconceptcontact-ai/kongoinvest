<?php

namespace App\Http\Controllers\Investor;

use App\Data\MatchData;
use App\Enums\MatchStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ComputeMatchesForInvestor;
use App\Models\MatchModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MatchController extends Controller
{
    public function index(Request $request): Response
    {
        $investorProfile = $request->user()->investorProfile()->firstOrFail();

        // Always scoped through the relationship — never MatchModel::all()
        // filtered client-side (same principle as spec §12 for projects).
        $matches = MatchModel::query()
            ->where('investor_profile_id', $investorProfile->id)
            ->with(['project.sectors', 'project.stages', 'investorProfile.user'])
            ->orderByDesc('score')
            ->get();

        return Inertia::render('Investor/Matches/Index', [
            'matches' => $matches->map(fn (MatchModel $m) => MatchData::fromModel($m)),
        ]);
    }

    public function markViewed(Request $request, MatchModel $match): RedirectResponse
    {
        $this->authorize('view', $match);

        if ($match->status->value === 'pending') {
            $match->update(['status' => MatchStatus::Viewed->value]);
        }

        return back();
    }

    public function dismiss(Request $request, MatchModel $match): RedirectResponse
    {
        $this->authorize('view', $match);

        $match->update(['status' => MatchStatus::Dismissed->value]);

        return back()->with('success', 'Match ignoré.');
    }

    /** Manual recompute — e.g. after the investor suspects their profile changed but no save happened yet. */
    public function recompute(Request $request): RedirectResponse
    {
        $investorProfile = $request->user()->investorProfile()->firstOrFail();

        ComputeMatchesForInvestor::dispatch($investorProfile);

        return back()->with('success', 'Recalcul des matches lancé.');
    }
}
