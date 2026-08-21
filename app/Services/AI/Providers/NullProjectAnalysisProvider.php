<?php

namespace App\Services\Ai\Providers;

use App\Contracts\ProjectAnalysisProviderInterface;
use App\Models\Project;
use App\Services\Ai\ProjectAnalysisResult;

class NullProjectAnalysisProvider implements ProjectAnalysisProviderInterface
{
    public function analyze(Project $project): ?ProjectAnalysisResult
    {
        return null;
    }
}
