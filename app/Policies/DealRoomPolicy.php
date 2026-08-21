<?php

namespace App\Policies;

use App\Models\DealRoom;
use App\Models\User;

class DealRoomPolicy
{
    public function view(User $user, DealRoom $dealRoom): bool
    {
        return $dealRoom->isParticipant($user) || $user->can('users.manage');
    }

    public function sendMessage(User $user, DealRoom $dealRoom): bool
    {
        return $dealRoom->isParticipant($user) && $user->can('messages.send');
    }
}
