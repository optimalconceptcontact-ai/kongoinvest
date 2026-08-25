<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\SavedProject;
use App\Models\User;

class SaveProject
{
    public function handle(User $investor, Project $project): void
    {
        $alreadySaved = SavedProject::where('investor_user_id', $investor->id)
            ->where('project_id', $project->id)
            ->exists();

        if (! $alreadySaved) {
            SavedProject::forceCreate([
                'investor_user_id' => $investor->id,
                'project_id' => $project->id,
            ]);
        }
    }
}
