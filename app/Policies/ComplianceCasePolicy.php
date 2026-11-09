<?php

namespace App\Policies;

use App\Models\ComplianceCase;
use App\Models\User;

class ComplianceCasePolicy
{
    public function view(User $user, ComplianceCase $case): bool
    {
        return $case->user_id === $user->id || $user->can('kyc.review') || $user->hasRole('admin');
    }

    public function manage(User $user): bool
    {
        return $user->can('kyc.review') || $user->hasRole('admin');
    }

    public function review(User $user): bool
    {
        return $user->can('kyc.review') || $user->hasRole('admin');
    }
}
