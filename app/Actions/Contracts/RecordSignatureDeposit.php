<?php

namespace App\Actions\Contracts;

use App\Enums\ContractDocumentStatus;
use App\Enums\SignatureStatus;
use App\Enums\WorkspaceStatus;
use App\Models\ContractDocument;
use App\Models\ContractSignature;
use App\Models\InvestmentContractWorkspace;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Enregistrement de la preuve de signature (prompt §11) : le
 * prestataire ou le dépôt des exemplaires — jamais une validation
 * d'interface. Toutes les signatures de la version signée devant
 * être complétées, la dernière complète fait basculer le document
 * en statut `signed` + horodatage de dépôt.
 */
class RecordSignatureDeposit
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $user, InvestmentContractWorkspace $workspace, ContractSignature $signature, array $input = []): ContractSignature
    {
        if ($signature->investment_contract_workspace_id !== $workspace->id) {
            throw ValidationException::withMessages([
                'signature' => 'Cette signature n\'appartient pas à cette opération.',
            ]);
        }

        if (! $workspace->isParticipant($user)) {
            throw ValidationException::withMessages([
                'workspace' => 'Seuls les participants de l\'opération enregistrent les signatures.',
            ]);
        }

        if ($workspace->isClosed() || $workspace->status === WorkspaceStatus::Cancelled) {
            throw ValidationException::withMessages([
                'workspace' => 'L\'opération est clôturée ou annulée : plus d\'enregistrement possible.',
            ]);
        }

        $data = Validator::make($input, [
            'signature_evidence' => ['required', 'array', 'max:50'],
        ])->validate();

        if ($signature->status !== SignatureStatus::Requested) {
            throw ValidationException::withMessages([
                'signature' => 'Cette signature est déjà traitée ('.$signature->status->label().') : la preuve s\'enregistre une seule fois.',
            ]);
        }

        $signature->forceFill([
            'status' => SignatureStatus::Completed->value,
            'signature_evidence' => $data['signature_evidence'],
            'signed_at' => now(),
        ])->save();

        $document = ContractDocument::query()->findOrFail($signature->contract_document_id);

        $allCompleted = $document->signatures()
            ->where('status', '!=', SignatureStatus::Completed->value)
            ->doesntExist();

        if ($allCompleted && $document->status !== ContractDocumentStatus::Signed) {
            $document->forceFill([
                'status' => ContractDocumentStatus::Signed->value,
                'signed_deposited_at' => now(),
            ])->save();
        }

        $workspace->forceFill(['last_activity_at' => now()])->save();

        $this->audit->log($user, 'workspace.signature_completed', $signature, [
            'workspace_id' => $workspace->id,
            'document_id' => $document->id,
            'signature_id' => $signature->id,
            'signer_role' => $signature->signer_role->value,
        ]);

        return $signature->fresh();
    }
}
