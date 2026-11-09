<?php

namespace App\Policies;

use App\Enums\WorkspaceStatus;
use App\Models\DealRoom;
use App\Models\InvestmentContractWorkspace;
use App\Models\User;

class InvestmentContractWorkspacePolicy
{
    public function view(User $user, InvestmentContractWorkspace $workspace): bool
    {
        if ($user->can('users.manage')) {
            return true;
        }

        if ($user->hasBlockedComplianceCase()
            || $workspace->founder()?->hasBlockedComplianceCase()
            || $workspace->investor()?->hasBlockedComplianceCase()) {
            return false;
        }

        return $workspace->isParticipant($user);
    }

    /**
     * L'espace contractuel s'ouvre depuis une salle de négociation :
     * un participant de la salle, jamais un tiers (le gating
     * abonnement « negotiation_tools » est vérifié en plus dans
     * l'action, sur les données de plan).
     */
    public function create(User $user, DealRoom $dealRoom): bool
    {
        return $dealRoom->isParticipant($user)
            && ! $user->hasBlockedComplianceCase()
            && $user->can('contracts.view');
    }

    /**
     * Consentement à l'analyse des conversations — chaque partie
     * consent pour elle-même (prompt §14), tant que l'opération n'est
     * pas clôturée ou annulée.
     */
    public function consent(User $user, InvestmentContractWorkspace $workspace): bool
    {
        return $workspace->isParticipant($user)
            && ! $user->hasBlockedComplianceCase()
            && $user->can('contracts.view')
            && ! $workspace->isClosed()
            && $workspace->status !== WorkspaceStatus::Cancelled;
    }

    /**
     * Positions, composantes et ronde de négociation — y compris de
     * toutes les parties sauf entre clôture et annulation.
     */
    public function update(User $user, InvestmentContractWorkspace $workspace): bool
    {
        return $workspace->isParticipant($user)
            && ! $user->hasBlockedComplianceCase()
            && ! $workspace->isClosed()
            && $workspace->status !== WorkspaceStatus::Cancelled;
    }
}
