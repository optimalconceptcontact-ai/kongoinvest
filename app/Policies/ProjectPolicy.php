<?php

namespace App\Policies;

use App\Enums\ProjectVisibility;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectLimitService;

class ProjectPolicy
{
    public function __construct(private ProjectLimitService $limits) {}

    /** Only authenticated users may view a project (never guests). */
    public function view(?User $user, Project $project): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->can('projects.view-all')) {
            return true;
        }

        // Spec: Entrepreneurs may ONLY view their own project.
        if ($user->isEntrepreneur()) {
            return $project->isOwnedBy($user);
        }

        if ($project->visibility === ProjectVisibility::Public && $project->status->value === 'published') {
            return true;
        }

        if ($project->isOwnedBy($user)) {
            return true;
        }

        // matched_investors / connection_only require Matching (Phase 5)
        // and Connections (Phase 8) to determine who qualifies. Until
        // those exist, nobody but the owner/admin can see them — a
        // closed default is the safe default, never an open one.
        return false;
    }

    /** Spec §12: only entrepreneurs, and only up to their plan's active-project limit. */
    public function create(User $user): bool
    {
        return $user->isEntrepreneur()
            && $user->can('projects.create')
            && $this->limits->canCreateProject($user);
    }

    public function update(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user) && $user->can('projects.update');
    }

    public function delete(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user) && $user->can('projects.delete');
    }

    public function publish(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user) && $user->can('projects.publish');
    }

    public function archive(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user) && $user->can('projects.update');
    }

    public function manageMedia(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user) && $user->can('projects.update');
    }

    public function viewAll(User $user): bool
    {
        return $user->can('projects.view-all');
    }
}
