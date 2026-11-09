<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Projects\PublishProject;
use App\Actions\Projects\RejectProject;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Notifications\ProjectModerationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Project::query()->with(['sectors', 'user', 'moderatedBy']);

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        $projects = $query->latest()->paginate(25)->withQueryString();

        return Inertia::render('Admin/Projects/Index', [
            'projects' => $projects->through(fn (Project $p) => [
                'id' => $p->id,
                'slug' => $p->slug,
                'title' => $p->title,
                'entrepreneurName' => $p->user->name,
                'status' => $p->status->value,
                'statusLabel' => $p->status->label(),
                'sectors' => $p->sectors->pluck('name'),
                'fundingTargetMinor' => $p->funding_target_minor,
                'fundingCurrency' => $p->funding_currency,
                'createdAt' => $p->created_at->toIso8601String(),
                'submittedAt' => $p->submitted_at?->toIso8601String(),
                'moderatedAt' => $p->moderated_at?->toIso8601String(),
                'moderationNotes' => $p->moderation_notes,
                'moderatorName' => $p->moderatedBy?->name,
            ]),
            'filters' => $request->only('status'),
            'statuses' => collect(ProjectStatus::cases())
                ->map(fn (ProjectStatus $status) => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ])
                ->all(),
            'counts' => collect(ProjectStatus::cases())
                ->mapWithKeys(fn (ProjectStatus $status) => [
                    $status->value => Project::where('status', $status->value)->count(),
                ])
                ->all(),
            'canModerate' => (bool) $request->user()?->can('projects.moderate'),
        ]);
    }

    public function approve(Request $request, Project $project, PublishProject $action): RedirectResponse
    {
        $this->authorize('moderate', $project);

        $action->handle($project, $request->user());

        $project->user?->notify(new ProjectModerationNotification($project, true));

        return back()->with('success', 'Projet approuvé et publié.');
    }

    public function reject(Request $request, Project $project, RejectProject $action): RedirectResponse
    {
        $this->authorize('moderate', $project);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $action->handle($project, $request->user(), $validated['reason']);

        $project->user?->notify(new ProjectModerationNotification($project, false, $validated['reason']));

        return back()->with('success', 'Projet rejeté, l\'entrepreneur a été notifié.');
    }
}
