<?php

namespace App\Actions\Contracts;

use App\Enums\ContractDocumentStatus;
use App\Enums\ContractPartyRole;
use App\Enums\SignatureMethod;
use App\Enums\SignatureStatus;
use App\Enums\WorkspaceStatus;
use App\Models\ContractDocument;
use App\Models\ContractDocumentVersion;
use App\Models\ContractSignature;
use App\Models\InvestmentContractWorkspace;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Compliance\InvestmentComplianceGateService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Demande de signature d'un document (prompt §11) : une ligne par
 * signataire — fondateur ET investisseur de la salle, chacun pour
 * sa partie. La signature est la preuve du prestataire ou le dépôt
 * des exemplaires signés, jamais une validation d'interface. Par
 * défaut : signature externe (dépôt d'exemplaires).
 */
class RequestContractSignatures
{
    public function __construct(
        private AuditLogger $audit,
        private InvestmentComplianceGateService $compliance,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return list<ContractSignature>
     */
    public function handle(User $user, InvestmentContractWorkspace $workspace, ContractDocument $document, array $input = []): array
    {
        if ($document->investment_contract_workspace_id !== $workspace->id) {
            throw ValidationException::withMessages([
                'document' => 'Ce document n\'appartient pas à cette opération.',
            ]);
        }

        if (! $workspace->isParticipant($user)) {
            throw ValidationException::withMessages([
                'workspace' => 'Seuls les participants de l\'opération demandent les signatures.',
            ]);
        }

        if ($workspace->isClosed() || $workspace->status === WorkspaceStatus::Cancelled) {
            throw ValidationException::withMessages([
                'workspace' => 'L\'opération est clôturée ou annulée : plus de demande possible.',
            ]);
        }

        $this->compliance->assertAllows(
            $this->compliance->evaluateWorkspace(
                $workspace,
                InvestmentComplianceGateService::STAGE_SIGNATURE,
            ),
            'workspace',
        );

        $data = Validator::make($input, [
            'method' => ['sometimes', Rule::enum(SignatureMethod::class)],
        ])->validate();

        if (! $document->hasBothApprovals()) {
            throw ValidationException::withMessages([
                'document' => 'Les signatures se demandent sur un document approuvé par les deux parties.',
            ]);
        }

        if ($document->status === ContractDocumentStatus::Signed) {
            throw ValidationException::withMessages([
                'document' => 'Ce document est déjà signé : les signatures se demandent une seule fois.',
            ]);
        }

        /** @var ContractDocumentVersion $version */
        $version = $document->versions()->firstOrFail(); // versions triées — la plus récente d'abord

        $founder = $workspace->founder();
        $investor = $workspace->investor();

        if ($founder === null || $investor === null) {
            throw ValidationException::withMessages([
                'workspace' => 'Les deux parties de l\'opération doivent être identifiées.',
            ]);
        }

        $signatures = [];

        foreach ([
            [ContractPartyRole::Founder, $founder],
            [ContractPartyRole::Investor, $investor],
        ] as [$role, $signerUser]) {
            // Une demande par signataire et par version : relance
            // idempotente, jamais de doublon de demande.
            $existing = ContractSignature::query()
                ->where('contract_document_version_id', $version->id)
                ->where('signer_user_id', $signerUser->id)
                ->where('signer_role', $role->value)
                ->first();

            if ($existing !== null) {
                $signatures[] = $existing;

                continue;
            }

            $signatures[] = ContractSignature::forceCreate([
                'investment_contract_workspace_id' => $workspace->id,
                'contract_document_id' => $document->id,
                'contract_document_version_id' => $version->id,
                'signer_user_id' => $signerUser->id,
                'signer_role' => $role->value,
                'method' => $data['method'] ?? SignatureMethod::External->value,
                'status' => SignatureStatus::Requested->value,
                'requested_by_user_id' => $user->id,
                'requested_at' => now(),
            ]);
        }

        if ($document->status === ContractDocumentStatus::Approved) {
            $document->forceFill(['status' => ContractDocumentStatus::PendingSignature->value])->save();
        }

        $workspace->forceFill(['last_activity_at' => now()])->save();

        $this->audit->log($user, 'workspace.signatures_requested', $document, [
            'workspace_id' => $workspace->id,
            'document_id' => $document->id,
            'version' => $version->version,
            'method' => $data['method'] ?? SignatureMethod::External->value,
        ]);

        return $signatures;
    }
}
