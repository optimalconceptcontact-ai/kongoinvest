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
 * Approbation commerciale d'un document par une partie (prompt
 * §11 — double circuit de validation). Chaque parti approuve pour
 * elle-même (horodatage dédié) : le document n'est approuvé
 * qu'avec les DEUX horodatages — jamais par un statut assigné.
 */
class ApproveContractDocument
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
                'workspace' => 'Seuls les participants de l\'opération approuvent les documents.',
            ]);
        }

        if ($workspace->isClosed() || $workspace->status === WorkspaceStatus::Cancelled) {
            throw ValidationException::withMessages([
                'workspace' => 'L\'opération est clôturée ou annulée : plus d\'approbation possible.',
            ]);
        }

        $this->compliance->assertAllows(
            $this->compliance->evaluateWorkspace(
                $workspace,
                InvestmentComplianceGateService::STAGE_SIGNATURE,
            ),
            'workspace',
        );

        $side = $workspace->sideOf($user);

        if ($side === null) {
            throw ValidationException::withMessages([
                'document' => 'Seuls les fondateurs et investisseurs de l\'opération approuvent les documents.',
            ]);
        }

        if (in_array($document->status, [ContractDocumentStatus::Signed, ContractDocumentStatus::Archived, ContractDocumentStatus::Cancelled], true)) {
            throw ValidationException::withMessages([
                'document' => 'Ce document est en état final ('.$document->status->label().') : plus d\'approbation possible.',
            ]);
        }

        $document->forceFill([
            $side === 'founder' ? 'founder_approved_at' : 'investor_approved_at' => now(),
        ]);

        if ($document->hasBothApprovals()) {
            // Statut dérivé du double horodatage (source de vérité),
            // pas l'inverse.
            $document->status = ContractDocumentStatus::Approved;
        }

        $document->save();

        $workspace->forceFill(['last_activity_at' => now()])->save();

        $this->audit->log($user, 'workspace.document_approved', $document, [
            'workspace_id' => $workspace->id,
            'document_id' => $document->id,
            'side' => $side,
            'both_approved' => $document->status === ContractDocumentStatus::Approved,
        ]);

        return $document->fresh();
    }
}
