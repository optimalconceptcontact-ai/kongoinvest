<?php

namespace App\Actions\Projects;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Validation\ValidationException;

class SubmitProjectForReview
{
    /**
     * Publishing is now a moderated transition: the entrepreneur submits a
     * complete project, an administrator approves or rejects it. The same
     * readiness checks as before apply at submission time so a half-empty
     * project never reaches the review queue.
     */
    public function handle(Project $project): Project
    {
        if (! in_array($project->status, [ProjectStatus::Draft, ProjectStatus::Rejected], true)) {
            throw ValidationException::withMessages([
                'publish' => 'Ce projet ne peut pas être soumis dans son état actuel.',
            ]);
        }

        if ($project->sectors()->count() === 0 || $project->stages()->count() === 0) {
            throw ValidationException::withMessages([
                'publish' => 'Sélectionnez au moins un secteur et un stade avant de soumettre le projet.',
            ]);
        }

        if (blank($project->funding_target_minor)) {
            throw ValidationException::withMessages([
                'publish' => 'Indiquez un objectif de financement avant de soumettre le projet.',
            ]);
        }

        // status/moderation fields are deliberately excluded from
        // Project::$fillable (only actions may set them) — update()
        // would silently no-op on them, so set the attributes directly.
        $project->status = ProjectStatus::PendingReview;
        $project->submitted_at = now();
        $project->moderated_at = null;
        $project->moderation_notes = null;
        $project->moderated_by_id = null;
        $project->save();

        return $project->fresh();
    }
}
