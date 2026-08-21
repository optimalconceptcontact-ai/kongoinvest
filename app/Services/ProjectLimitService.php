<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Models\User;

class ProjectLimitService
{
    /**
     * Fallback only — used if a user somehow has no active subscription
     * at all (shouldn't happen; CreateNewUser always assigns Free at
     * registration via SubscriptionService::assignFreePlan). Matches the
     * Free plan's own `max_active_projects` limit (spec §29) so the
     * fallback and the real value agree.
     */
    private const DEFAULT_ACTIVE_PROJECT_LIMIT = 1;

    public function __construct(private SubscriptionService $subscriptions) {}

    public function limitFor(User $user): int
    {
        return $this->subscriptions->limit($user, 'max_active_projects')
            ?? self::DEFAULT_ACTIVE_PROJECT_LIMIT;
    }

    public function activeProjectCount(User $user): int
    {
        return $user->projects()
            ->whereIn('status', array_map(fn ($s) => $s->value, ProjectStatus::activeStatuses()))
            ->count();
    }

    public function canCreateProject(User $user): bool
    {
        return $this->activeProjectCount($user) < $this->limitFor($user);
    }
}
