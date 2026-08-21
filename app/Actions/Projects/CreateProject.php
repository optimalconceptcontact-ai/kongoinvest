<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateProject
{
    public function handle(User $entrepreneur, array $data): Project
    {
        return DB::transaction(function () use ($entrepreneur, $data) {
            $project = $entrepreneur->projects()->create(
                collect($data)->except(['sectors', 'stages'])->toArray(),
            );

            $project->sectors()->sync($data['sectors']);
            $project->stages()->sync($data['stages']);

            return $project->load(['sectors', 'stages']);
        });
    }
}
