<?php

namespace App\Http\Controllers\Entrepreneur;

use App\Data\MatchData;
use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MatchController extends Controller
{
    public function index(Request $request): Response
    {
        // Scoped through the user's own projects — an entrepreneur can
        // never see matches belonging to someone else's project, even by
        // guessing an ID (spec §12).
        $matches = MatchModel::query()
            ->whereIn('project_id', $request->user()->projects()->pluck('id'))
            ->with(['project', 'investorProfile.user'])
            ->orderByDesc('score')
            ->get();

        return Inertia::render('Entrepreneur/Matches/Index', [
            'matches' => $matches->map(fn (MatchModel $m) => MatchData::fromModel($m)),
        ]);
    }
}
