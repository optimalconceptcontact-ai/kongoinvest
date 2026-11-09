<?php

namespace App\Policies;

use App\Models\DealRoom;
use App\Models\User;

class DealRoomPolicy
{
    public function view(User $user, DealRoom $dealRoom): bool
    {
        // A blocked/suspended compliance case freezes access to the
        // sensitive deal room for the affected party until it is lifted.
        if ($user->hasBlockedComplianceCase()) {
            return false;
        }

        if ($dealRoom->entrepreneur?->hasBlockedComplianceCase()) {
            return false;
        }

        if ($dealRoom->investor?->hasBlockedComplianceCase()) {
            return false;
        }

        return $dealRoom->isParticipant($user) || $user->can('users.manage');
    }

    public function sendMessage(User $user, DealRoom $dealRoom): bool
    {
        if ($user->hasBlockedComplianceCase()) {
            return false;
        }

        return $dealRoom->isParticipant($user) && $user->can('messages.send');
    }
}
