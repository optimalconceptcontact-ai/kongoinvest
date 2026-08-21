<?php

namespace App\Policies;

use App\Enums\ConnectionStatus;
use App\Models\Connection;
use App\Models\User;

class ConnectionPolicy
{
    public function view(User $user, Connection $connection): bool
    {
        return $connection->investorProfile->user_id === $user->id
            || $connection->project->user_id === $user->id
            || $user->can('users.manage');
    }

    /** Spec §13: only investors may initiate a connection request. */
    public function create(User $user): bool
    {
        return $user->isInvestor() && $user->can('connections.create');
    }

    /** Only the project owner may accept or reject — never the investor who sent it. */
    public function respond(User $user, Connection $connection): bool
    {
        return $connection->project->user_id === $user->id
            && $connection->status === ConnectionStatus::Pending
            && $user->can('connections.accept');
    }

    /** Only the requesting investor may cancel their own pending request. */
    public function cancel(User $user, Connection $connection): bool
    {
        return $connection->investorProfile->user_id === $user->id
            && $connection->status === ConnectionStatus::Pending;
    }
}
