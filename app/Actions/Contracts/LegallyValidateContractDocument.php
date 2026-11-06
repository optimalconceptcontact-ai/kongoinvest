<?php

namespace App\Actions\Contracts;

use App\Enums\WorkspaceStatus;
use App\Models\ContractDocument;
use App\Models\InvestmentContractWorkspace;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

/**
 * Validation juridique d'un document (prompt §16, règle 15) :
 * circuit séparé de la validation commerciale, identifiable —
 * réservé à la gestion utilisateurs (users.manage). Aucun « rôle
 * juridique » n'existe : le droit est la seule frontière.
 */
class LegallyValidateContractDocument
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(User $user, InvestmentContractWorkspace $workspace, ContractDocument $document): ContractDocument
    {
        if ($document->investment_contract_workspace_id !== $workspace->id) {
            throw ValidationException::withMessages([
                'document' => 'Ce document n\'appartient pas à cette opération.',
            ]);
        }

        if (! $user->can('users.manage')) {
            throw ValidationException::withMessages([
                'document' => 'Seule la gestion juridique valide les documents.',
            ]);
        }

        if ($workspace->isClosed() || $workspace->status === WorkspaceStatus::Cancelled) {
            throw ValidationException::withMessages([
                'workspace' => 'L\'opération est clôturée ou annulée : plus de validation possible.',
            ]);
        }

        $document->forceFill([
            'legal_validated_at' => now(),
            'legal_validated_by_user_id' => $user->id,
        ])->save();

        $this->audit->log($user, 'workspace.document_legal_validated', $document, [
            'workspace_id' => $workspace->id,
            'document_id' => $document->id,
        ]);

        return $document->fresh();
    }
}
