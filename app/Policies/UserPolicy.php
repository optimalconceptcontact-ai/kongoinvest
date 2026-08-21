<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /** A user can always view their own account. Admins can view any. */
    public function view(User $actor, User $target): bool
    {
        return $actor->id === $target->id || $actor->can('users.view');
    }

    /** A user can always update their own account settings. */
    public function update(User $actor, User $target): bool
    {
        return $actor->id === $target->id || $actor->can('users.manage');
    }

    /** Only admins with users.manage may change another user's roles. */
    public function manageRoles(User $actor, User $target): bool
    {
        return $actor->id !== $target->id && $actor->can('users.manage');
    }

    public function delete(User $actor, User $target): bool
    {
        return $actor->id === $target->id || $actor->can('users.manage');
    }

    public function viewAdminDashboard(User $actor): bool
    {
        return $actor->can('admin.dashboard');
    }
}
