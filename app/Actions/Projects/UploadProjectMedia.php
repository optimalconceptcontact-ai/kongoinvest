<?php

namespace App\Actions\Projects;

use App\Models\Project;
use Illuminate\Http\UploadedFile;

class UploadProjectMedia
{
    /** @param  UploadedFile[]  $files */
    public function handle(Project $project, string $collection, array $files): Project
    {
        foreach ($files as $file) {
            $project->addMedia($file)->toMediaCollection($collection);
        }

        return $project->fresh();
    }
}
