<?php

namespace App\Policies;

use App\Enums\EscrowStatus;
use App\Models\Escrow;
use App\Models\User;

class EscrowPolicy
{
    public function view(User $user, Escrow $escrow): bool
    {
        return $escrow->isParticipant($user) || $user->can('users.manage');
    }

    public function fund(User $user, Escrow $escrow): bool
    {
        return $user->id === $escrow->investor_user_id
            && $escrow->status === EscrowStatus::Pending
            && $user->can('escrow.create');
    }

    /** Spec §35: only the investor confirms release — never the entrepreneur, never automatically. */
    public function release(User $user, Escrow $escrow): bool
    {
        return $user->id === $escrow->investor_user_id
            && $escrow->status === EscrowStatus::Held
            && $user->can('escrow.release');
    }

    public function requestRefund(User $user, Escrow $escrow): bool
    {
        return $user->id === $escrow->investor_user_id
            && $escrow->status === EscrowStatus::Held
            && $user->can('escrow.refund');
    }

    /** Either participant may raise a dispute — this isn't investor-only like release/refund. */
    public function dispute(User $user, Escrow $escrow): bool
    {
        return $escrow->isParticipant($user)
            && in_array($escrow->status, EscrowStatus::disputable(), true);
    }
}
