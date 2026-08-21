<?php

namespace App\Policies;

use App\Models\MatchModel;
use App\Models\User;

class MatchPolicy
{
    public function view(User $user, MatchModel $match): bool
    {
        if ($user->can('users.manage')) { // admin escape hatch, not a role check
            return true;
        }

        $isTheInvestor = $match->investorProfile->user_id === $user->id;
        $isTheProjectOwner = $match->project->user_id === $user->id;

        return ($isTheInvestor || $isTheProjectOwner) && $user->can('matches.view');
    }
}
