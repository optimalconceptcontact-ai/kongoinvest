<?php

namespace App\Actions\Projects;

use App\Enums\ProjectStatus;
use App\Enums\ProjectVisibility;
use App\Jobs\ComputeMatchesForProject;
use App\Models\Project;
use Illuminate\Validation\ValidationException;

class PublishProject
{
    /**
     * A project can't be published half-empty — this is server-side, not
     * a disabled button (spec §100). Kept intentionally minimal for
     * Phase 3; Phase 5 (AI analysis) may add its own pre-publish checks
     * later without this action needing to change shape.
     */
    public function handle(Project $project): Project
    {
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

        // status/visibility/published_at are deliberately excluded from
        // Project::$fillable (only this action may set them) — update()
        // would silently no-op on them, so set the attributes directly.
        $project->status = ProjectStatus::Published;
        if ($project->visibility === ProjectVisibility::Private) {
            $project->visibility = ProjectVisibility::Public;
        }
        $project->published_at = now();
        $project->save();

        // Spec §22-24: publishing is what triggers matching. Queued —
        // scoring every onboarded investor (including an AI call per
        // pair, if enabled) is exactly the kind of work that shouldn't
        // block this request (spec §115).
        ComputeMatchesForProject::dispatch($project->fresh());

        return $project->fresh();
    }
}
