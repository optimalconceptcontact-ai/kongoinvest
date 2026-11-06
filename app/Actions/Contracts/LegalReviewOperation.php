<?php

namespace App\Actions\Contracts;

use App\Models\InvestmentContractWorkspace;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

/**
 * Revue juridique de l'OPÉRATION (rebutable seulement par la
 * gestion juridique, `users.manage`) : horodatée et attribuée
 * (legal_verified_by_user_id), c'est l'une des clés de clôture —
 * jamais un simple bouton partie.
 */
class LegalReviewOperation
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(User $user, InvestmentContractWorkspace $workspace): InvestmentContractWorkspace
    {
        if ($workspace->isClosed()) {
            throw ValidationException::withMessages([
                'workspace' => 'L\'opération est clôturée : la revue juridique reste en historique.',
            ]);
        }

        if (! $user->can('users.manage')) {
            throw ValidationException::withMessages([
                'workspace' => 'Seule la gestion juridique conduit la revue de l\'opération.',
            ]);
        }

        if ($workspace->legal_reviewed_at !== null) {
            return $workspace;
        }

        $workspace->forceFill([
            'legal_reviewed_at' => now(),
            'legal_verified_by_user_id' => $user->id,
        ])->save();

        $this->audit->log($user, 'workspace.legal_review', $workspace, [
            'workspace_id' => $workspace->id,
        ]);

        return $workspace->fresh();
    }
}
