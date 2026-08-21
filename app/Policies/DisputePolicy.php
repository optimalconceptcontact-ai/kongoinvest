<?php

namespace App\Policies;

use App\Models\Dispute;
use App\Models\User;

class DisputePolicy
{
    public function view(User $user, Dispute $dispute): bool
    {
        return $dispute->escrow->isParticipant($user) || $user->can('users.manage');
    }

    /** Only admin/compliance resolves disputes — never a participant, however "obviously" one side is right. */
    public function resolve(User $user): bool
    {
        return $user->can('users.manage');
    }
}
