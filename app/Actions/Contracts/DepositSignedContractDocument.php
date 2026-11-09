<?php

namespace App\Actions\Contracts;

use App\Enums\ContractDocumentStatus;
use App\Enums\WorkspaceStatus;
use App\Models\ContractDocument;
use App\Models\InvestmentContractWorkspace;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Compliance\InvestmentComplianceGateService;
use Illuminate\Validation\ValidationException;

/**
 * Dépôt de l'exemplaire signé d'un document (signature externe,
 * prompt §11). Conditions : les DEUX approbations commerciales et
 * la validation juridique — un document sans circuit de validation
 * complet ne se voit pas déclarer signé.
 */
class DepositSignedContractDocument
{
    public function __construct(
        private AuditLogger $audit,
        private InvestmentComplianceGateService $compliance,
    ) {}

    public function handle(User $user, InvestmentContractWorkspace $workspace, ContractDocument $document): ContractDocument
    {
        if ($document->investment_contract_workspace_id !== $workspace->id) {
            throw ValidationException::withMessages([
                'document' => 'Ce document n\'appartient pas à cette opération.',
            ]);
        }

        if (! $workspace->isParticipant($user)) {
            throw ValidationException::withMessages([
                'workspace' => 'Seuls les participants de l\'opération déposent les exemplaires signés.',
            ]);
        }

        if ($workspace->isClosed() || $workspace->status === WorkspaceStatus::Cancelled) {
            throw ValidationException::withMessages([
                'workspace' => 'L\'opération est clôturée ou annulée : plus de dépôt possible.',
            ]);
        }

        $this->compliance->assertAllows(
            $this->compliance->evaluateWorkspace(
                $workspace,
                InvestmentComplianceGateService::STAGE_SIGNATURE,
            ),
            'workspace',
        );

        if (! $document->hasBothApprovals()) {
            throw ValidationException::withMessages([
                'document' => 'Le document doit d\'abord être approuvé par les deux parties.',
            ]);
        }

        if ($document->legal_validated_at === null) {
            throw ValidationException::withMessages([
                'document' => 'Le document doit d\'abord être validé juridiquement.',
            ]);
        }

        if (! in_array($document->status, [ContractDocumentStatus::Approved, ContractDocumentStatus::PendingSignature], true)) {
            throw ValidationException::withMessages([
                'document' => 'Ce document est en état final ('.$document->status->label().') : plus de dépôt possible.',
            ]);
        }

        $document->forceFill([
            'signed_deposited_at' => now(),
            'status' => ContractDocumentStatus::Signed->value,
        ])->save();

        $workspace->forceFill(['last_activity_at' => now()])->save();

        $this->audit->log($user, 'workspace.signed_deposited', $document, [
            'workspace_id' => $workspace->id,
            'document_id' => $document->id,
            'version' => $document->current_version,
        ]);

        return $document->fresh();
    }
}
