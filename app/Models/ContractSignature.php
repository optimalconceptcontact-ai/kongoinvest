<?php

namespace App\Models;

use App\Enums\ContractPartyRole;
use App\Enums\SignatureMethod;
use App\Enums\SignatureStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $investment_contract_workspace_id
 * @property int $contract_document_id
 * @property int $contract_document_version_id
 * @property int $signer_user_id
 * @property ContractPartyRole $signer_role
 * @property SignatureMethod $method
 * @property SignatureStatus $status
 * @property string|null $provider
 * @property string|null $provider_reference
 * @property array<string, mixed>|null $provider_metadata
 * @property array<string, mixed>|null $signature_evidence
 * @property int|null $requested_by_user_id
 * @property Carbon|null $requested_at
 * @property Carbon|null $signed_at
 */
class ContractSignature extends Model
{
    protected $fillable = []; // every field is set exclusively via Actions — signature data is never user-request writable

    protected function casts(): array
    {
        return [
            'signer_role' => ContractPartyRole::class,
            'method' => SignatureMethod::class,
            'status' => SignatureStatus::class,
            'provider_metadata' => 'array',
            'signature_evidence' => 'array',
            'requested_at' => 'datetime',
            'signed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<InvestmentContractWorkspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(InvestmentContractWorkspace::class, 'investment_contract_workspace_id');
    }

    /** @return BelongsTo<ContractDocument, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(ContractDocument::class, 'contract_document_id');
    }

    /** @return BelongsTo<ContractDocumentVersion, $this> */
    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(ContractDocumentVersion::class, 'contract_document_version_id');
    }

    /** @return BelongsTo<User, $this> */
    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_user_id');
    }
}
