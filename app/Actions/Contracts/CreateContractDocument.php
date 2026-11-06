<?php

namespace App\Actions\Contracts;

use App\Enums\ContractDocumentStatus;
use App\Enums\ContractDocumentType;
use App\Enums\DocumentConfidentiality;
use App\Enums\WorkspaceStatus;
use App\Models\ContractDocument;
use App\Models\ContractDocumentVersion;
use App\Models\ContractFinancingComponent;
use App\Models\InvestmentContractWorkspace;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Dépôt d'un document contractuel par une partie (prompt §9, §11).
 * Le dépôt crée la version 1 avec l'empreinte SHA-256 du contenu
 * exact — les versions et l'empreinte restent inaltérables. Le
 * contenu structuré (JSON) est stocké tel qu'il a été fourni :
 * aucune normalisation de texte au vol.
 */
class CreateContractDocument
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $user, InvestmentContractWorkspace $workspace, array $input = []): ContractDocument
    {
        if (! $workspace->isParticipant($user)) {
            throw ValidationException::withMessages([
                'workspace' => 'Seuls les participants de l\'opération peuvent déposer un document.',
            ]);
        }

        if ($workspace->isClosed() || $workspace->status === WorkspaceStatus::Cancelled) {
            throw ValidationException::withMessages([
                'workspace' => 'L\'opération est clôturée ou annulée : plus de dépôt de document possible.',
            ]);
        }

        $data = Validator::make($input, [
            'type' => ['required', Rule::enum(ContractDocumentType::class)],
            'title' => ['required', 'string', 'min:3', 'max:300'],
            'content' => ['required', 'array', 'max:200'],
            'confidentiality' => ['sometimes', Rule::enum(DocumentConfidentiality::class)],
            'source_component_id' => ['sometimes', 'integer'],
        ])->validate();

        $sourceComponentId = $data['source_component_id'] ?? null;

        if (! $this->sourceComponentOfOperation($workspace, $sourceComponentId)) {
            throw ValidationException::withMessages([
                'source_component_id' => 'La composante référencée n\'appartient pas à cette opération.',
            ]);
        }

        $content = $data['content'];
        $hash = hash('sha256', json_encode($content, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        $document = ContractDocument::forceCreate([
            'investment_contract_workspace_id' => $workspace->id,
            'type' => $data['type'],
            'title' => trim((string) $data['title']),
            'status' => ContractDocumentStatus::InReview->value,
            'confidentiality' => ($data['confidentiality'] ?? DocumentConfidentiality::Shared->value),
            'current_version' => 1,
            'source_component_id' => $sourceComponentId,
        ]);

        ContractDocumentVersion::forceCreate([
            'contract_document_id' => $document->id,
            'version' => 1,
            'title' => trim((string) $data['title']),
            'content' => $content,
            'hash' => $hash,
            'created_by_user_id' => $user->id,
        ]);

        $workspace->forceFill(['last_activity_at' => now()])->save();

        $this->audit->log($user, 'workspace.document_created', $document, [
            'workspace_id' => $workspace->id,
            'document_id' => $document->id,
            'type' => $document->type->value,
            'hash' => $hash,
        ]);

        return $document->fresh();
    }

    private function sourceComponentOfOperation(InvestmentContractWorkspace $workspace, mixed $sourceComponentId): bool
    {
        if ($sourceComponentId === null) {
            return true;
        }

        return ContractFinancingComponent::query()
            ->where('investment_contract_workspace_id', $workspace->id)
            ->whereKey((int) $sourceComponentId)
            ->exists();
    }
}
