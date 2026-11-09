<?php

namespace App\Actions\Projects;

use App\Enums\ProjectStatus;
use App\Enums\ProjectVisibility;
use App\Jobs\ComputeMatchesForProject;
use App\Models\Project;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PublishProject
{
    /**
     * Administrator approval of a project that is waiting for review.
     * The moderation fields are excluded from Project::$fillable, so they
     * are assigned directly here and nowhere else.
     */
    public function handle(Project $project, ?User $moderator = null): Project
    {
        if ($project->status !== ProjectStatus::PendingReview) {
            throw ValidationException::withMessages([
                'publish' => 'Seul un projet en attente de validation peut être approuvé.',
            ]);
        }

        if ($project->sectors()->count() === 0 || $project->stages()->count() === 0) {
            throw ValidationException::withMessages([
                'publish' => 'Sélectionnez au moins un secteur et un stade avant de publier.',
            ]);
        }

        if (blank($project->funding_target_minor)) {
            throw ValidationException::withMessages([
                'publish' => 'Indiquez un objectif de financement avant de publier.',
            ]);
        }

        $project->status = ProjectStatus::Published;
        if ($project->visibility === ProjectVisibility::Private) {
            $project->visibility = ProjectVisibility::Public;
        }
        $project->published_at = now();
        $project->moderated_at = now();
        $project->moderated_by_id = $moderator?->id;
        $project->moderation_notes = null;
        $project->save();

        // Spec §22-24: publishing is what triggers matching. Queued —
        // scoring every onboarded investor (including an AI call per
        // pair, if enabled) is exactly the kind of work that shouldn't
        // block this request (spec §115).
        ComputeMatchesForProject::dispatch($project->fresh());

        return $project->fresh();
    }
}
