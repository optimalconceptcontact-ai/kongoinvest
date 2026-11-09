<?php

namespace App\Actions\Projects;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RejectProject
{
    public function handle(Project $project, User $moderator, string $reason): Project
    {
        if ($project->status !== ProjectStatus::PendingReview) {
            throw ValidationException::withMessages([
                'moderation' => 'Seul un projet en attente de validation peut être rejeté.',
            ]);
        }

        $project->status = ProjectStatus::Rejected;
        $project->moderated_at = now();
        $project->moderated_by_id = $moderator->id;
        $project->moderation_notes = $reason;
        $project->save();

        return $project->fresh();
    }
}
