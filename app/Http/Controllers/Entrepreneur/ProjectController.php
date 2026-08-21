<?php

namespace App\Http\Controllers\Entrepreneur;

use App\Actions\Projects\ArchiveProject;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\PublishProject;
use App\Actions\Projects\UpdateProject;
use App\Data\AiConversationData;
use App\Data\ProjectData;
use App\Data\SectorData;
use App\Data\StageData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\Sector;
use App\Models\Stage;
use App\Enums\AiInteractionType;
use App\Enums\SubscriptionFeature;
use App\Models\AiConversation;
use App\Services\ProjectLimitService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request, ProjectLimitService $limits): Response
    {
        // Spec §12: always scoped through the relationship — never
        // Project::all() with a client-side filter.
        $projects = $request->user()->projects()->with(['sectors', 'stages'])->latest()->get();

        return Inertia::render('Entrepreneur/Projects/Index', [
            'projects' => $projects->map(fn (Project $p) => ProjectData::fromModel($p)),
            'canCreateProject' => $limits->canCreateProject($request->user()),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Project::class);

        return Inertia::render('Entrepreneur/Projects/Form', [
            'project' => null,
            'sectors' => Sector::where('is_active', true)->orderBy('order')->get()->map(fn ($s) => SectorData::fromModel($s)),
            'stages' => Stage::where('is_active', true)->orderBy('order')->get()->map(fn ($s) => StageData::fromModel($s)),
        ]);
    }

    public function store(StoreProjectRequest $request, CreateProject $action): RedirectResponse
    {
        $project = $action->handle($request->user(), $request->validated());

        return redirect()
            ->route('entrepreneur.projects.edit', $project)
            ->with('success', 'Projet créé. Ajoutez des médias puis publiez-le quand vous êtes prêt.');
    }

    public function edit(Request $request, Project $project, SubscriptionService $subscriptions): Response
    {
        $this->authorize('update', $project);

        $hasCoach = $subscriptions->hasFeature($request->user(), SubscriptionFeature::AiCoach);

        $coachConversation = $hasCoach
            ? AiConversation::where('user_id', $request->user()->id)
                ->where('project_id', $project->id)
                ->where('type', AiInteractionType::EntrepreneurCoach->value)
                ->with('messages')
                ->first()
            : null;

        return Inertia::render('Entrepreneur/Projects/Form', [
            'project' => ProjectData::fromModel($project, full: true),
            'sectors' => Sector::where('is_active', true)->orderBy('order')->get()->map(fn ($s) => SectorData::fromModel($s)),
            'stages' => Stage::where('is_active', true)->orderBy('order')->get()->map(fn ($s) => StageData::fromModel($s)),
            'hasCoachFeature' => $hasCoach,
            'coachConversation' => $coachConversation ? AiConversationData::fromModel($coachConversation) : null,
            'media' => [
                'images' => $project->getMedia('project-images')->map(fn ($m) => [
                    'id' => $m->id,
                    'url' => $m->getUrl(),
                    'thumbUrl' => $m->getUrl('thumb'),
                    'name' => $m->name,
                ]),
                // Private disk — never a direct URL, only a signed one
                // generated fresh on every page load (spec §43).
                'documents' => $project->getMedia('project-documents')->map(fn ($m) => [
                    'id' => $m->id,
                    'name' => $m->file_name,
                    'size' => $m->size,
                    'downloadUrl' => URL::temporarySignedRoute('projects.documents.download', now()->addMinutes(10), ['media' => $m->id]),
                ]),
            ],
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project, UpdateProject $action): RedirectResponse
    {
        $action->handle($project, $request->validated());

        return back()->with('success', 'Projet mis à jour.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete(); // soft delete — spec §49 audit trail

        return redirect()->route('entrepreneur.projects.index')->with('success', 'Projet supprimé.');
    }

    public function publish(Request $request, Project $project, PublishProject $action): RedirectResponse
    {
        $this->authorize('publish', $project);

        $action->handle($project);

        return back()->with('success', 'Projet publié.');
    }

    public function archive(Request $request, Project $project, ArchiveProject $action): RedirectResponse
    {
        $this->authorize('archive', $project);

        $action->handle($project);

        return back()->with('success', 'Projet archivé — vous pouvez maintenant en créer un nouveau.');
    }
}
