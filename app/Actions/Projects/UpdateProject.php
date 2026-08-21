<?php

namespace App\Actions\Projects;

use App\Models\Project;
use Illuminate\Support\Facades\DB;

class UpdateProject
{
    public function handle(Project $project, array $data): Project
    {
        return DB::transaction(function () use ($project, $data) {
            $project->fill(collect($data)->except(['sectors', 'stages'])->toArray());
            $project->save();

            $project->sectors()->sync($data['sectors']);
            $project->stages()->sync($data['stages']);

            return $project->fresh(['sectors', 'stages']);
        });
    }
}
