<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;

class ContractPolicy
{
    public function view(User $user, Contract $contract): bool
    {
        return $contract->dealRoom->isParticipant($user) || $user->can('users.manage');
    }

    /**
     * Only the investor may request contract generation — the AI drafts
     * it from what was discussed, so the entrepreneur reviews/accepts or
     * rejects, but never initiates it themselves.
     */
    public function generate(User $user, \App\Models\DealRoom $dealRoom): bool
    {
        return $user->id === $dealRoom->investor_user_id && $user->can('contracts.view');
    }

    public function accept(User $user, Contract $contract): bool
    {
        return $contract->dealRoom->isParticipant($user)
            && ! $contract->hasAccepted($user)
            && $user->can('contracts.accept');
    }

    public function reject(User $user, Contract $contract): bool
    {
        return $contract->dealRoom->isParticipant($user) && ! $contract->isFullyAccepted();
    }
}
