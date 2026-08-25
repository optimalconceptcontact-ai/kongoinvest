<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\User;

class UnsaveProject
{
    public function handle(User $investor, Project $project): void
    {
        $investor->savedProjects()->where('project_id', $project->id)->delete();
    }
}
