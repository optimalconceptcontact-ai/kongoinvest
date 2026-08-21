<?php

namespace App\Actions\Projects;

use App\Enums\ProjectStatus;
use App\Models\Project;

class ArchiveProject
{
    /**
     * Archiving is what actually frees the Free-plan slot (spec §20) —
     * ProjectLimitService counts Draft + Published as active, so this is
     * the only path back to being able to create a new project.
     */
    public function handle(Project $project): Project
    {
        // status/archived_at are deliberately excluded from Project::$fillable
        // (only this action may set them) — update() would silently no-op
        // on them, so set the attributes directly.
        $project->status = ProjectStatus::Archived;
        $project->archived_at = now();
        $project->save();

        return $project->fresh();
    }
}
