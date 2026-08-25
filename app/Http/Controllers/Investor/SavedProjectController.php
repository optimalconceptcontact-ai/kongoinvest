<?php

namespace App\Http\Controllers\Investor;

use App\Actions\Projects\SaveProject;
use App\Actions\Projects\UnsaveProject;
use App\Data\ProjectData;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SavedProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $projects = Project::query()
            ->whereHas('savedBy', fn ($q) => $q->where('investor_user_id', $request->user()->id))
            ->with(['sectors', 'stages', 'user'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Investor/SavedProjects/Index', [
            'projects' => $projects->through(fn (Project $p) => ProjectData::fromModel($p)),
        ]);
    }

    public function store(Request $request, Project $project, SaveProject $action): RedirectResponse
    {
        $action->handle($request->user(), $project);

        return back()->with('success', 'Projet sauvegardé.');
    }

    public function destroy(Request $request, Project $project, UnsaveProject $action): RedirectResponse
    {
        $action->handle($request->user(), $project);

        return back()->with('success', 'Projet retiré des favoris.');
    }
}
