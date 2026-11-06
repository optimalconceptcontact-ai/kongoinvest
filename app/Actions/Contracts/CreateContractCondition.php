<?php

namespace App\Actions\Contracts;

use App\Enums\ContractConditionStatus;
use App\Enums\ContractConditionType;
use App\Enums\ContractPartyRole;
use App\Enums\WorkspaceStatus;
use App\Models\ContractCondition;
use App\Models\ContractDocument;
use App\Models\InvestmentContractWorkspace;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Création d'une condition suspensive × résolutive, obligation
 * ou engagement (covenant) (prompt §9). Toute condition bloque la
 * clôture tant qu'elle est en attente — créée en statut
 * `pending`, jamais « estimée » déjà levée.
 */
class CreateContractCondition
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $user, InvestmentContractWorkspace $workspace, array $input = []): ContractCondition
    {
        if (! $workspace->isParticipant($user)) {
            throw ValidationException::withMessages([
                'workspace' => 'Seuls les participants de l\'opération créent des conditions.',
            ]);
        }

        if ($workspace->isClosed() || $workspace->status === WorkspaceStatus::Cancelled) {
            throw ValidationException::withMessages([
                'workspace' => 'L\'opération est clôturée ou annulée : plus de condition possible.',
            ]);
        }

        $data = Validator::make($input, [
            'type' => ['required', Rule::enum(ContractConditionType::class)],
            'responsible_party' => ['sometimes', Rule::enum(ContractPartyRole::class)],
            'description' => ['required', 'string', 'min:5', 'max:5000'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'document_id' => ['sometimes', 'integer'],
            'source' => ['sometimes', 'array', 'max:50'],
        ])->validate();

        $documentId = $data['document_id'] ?? null;

        if ($documentId !== null) {
            $belongsToOperation = ContractDocument::query()
                ->whereKey((int) $documentId)
                ->where('investment_contract_workspace_id', $workspace->id)
                ->exists();

            if (! $belongsToOperation) {
                throw ValidationException::withMessages([
                    'document_id' => 'Le document référencé n\'appartient pas à cette opération.',
                ]);
            }
        }

        $condition = ContractCondition::forceCreate([
            'investment_contract_workspace_id' => $workspace->id,
            'contract_document_id' => $documentId,
            'type' => $data['type'],
            'responsible_party' => $data['responsible_party'] ?? ContractPartyRole::Mutual->value,
            'description' => trim((string) $data['description']),
            'due_date' => $data['due_date'] ?? null,
            'status' => ContractConditionStatus::Pending->value,
            'source' => $data['source'] ?? null,
        ]);

        $workspace->forceFill(['last_activity_at' => now()])->save();

        $this->audit->log($user, 'workspace.condition_created', $condition, [
            'workspace_id' => $workspace->id,
            'condition_id' => $condition->id,
            'type' => $condition->type->value,
        ]);

        return $condition->fresh();
    }
}
