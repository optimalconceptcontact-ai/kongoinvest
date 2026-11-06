<?php

namespace App\Actions\Contracts;

use App\Enums\WorkspaceStatus;
use App\Models\InvestmentContractWorkspace;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

/**
 * Annulation de l'opération : état terminal distinct de la
 * clôture. Toutes les autres actions refusent ensuite les
 * participants (structure figée, plus de clause, de document,
 * de condition, de signature).
 */
class CancelOperation
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(User $user, InvestmentContractWorkspace $workspace): InvestmentContractWorkspace
    {
        if ($workspace->isClosed() || $workspace->status === WorkspaceStatus::Cancelled) {
            throw ValidationException::withMessages([
                'workspace' => 'L\'opération est déjà clôturée ou annulée.',
            ]);
        }

        if (! $workspace->isParticipant($user)) {
            throw ValidationException::withMessages([
                'workspace' => 'Seuls les participants de l\'opération l\'annulent.',
            ]);
        }

        $workspace->forceFill([
            'status' => WorkspaceStatus::Cancelled->value,
            'last_activity_at' => now(),
        ])->save();

        $this->audit->log($user, 'workspace.cancelled', $workspace, [
            'workspace_id' => $workspace->id,
        ]);

        return $workspace->fresh();
    }
}
