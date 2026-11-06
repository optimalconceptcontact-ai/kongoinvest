<?php

namespace App\Models;

use App\Enums\ContractDocumentStatus;
use App\Enums\ContractDocumentType;
use App\Enums\DocumentConfidentiality;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $investment_contract_workspace_id
 * @property ContractDocumentType $type
 * @property string $title
 * @property ContractDocumentStatus $status
 * @property DocumentConfidentiality $confidentiality
 * @property int|null $current_version
 * @property int|null $source_component_id
 * @property array<string, mixed>|null $generation_context
 * @property string|null $generator_provider
 * @property Carbon|null $founder_approved_at
 * @property Carbon|null $investor_approved_at
 * @property Carbon|null $legal_validated_at
 * @property int|null $legal_validated_by_user_id
 * @property Carbon|null $signed_deposited_at
 */
class ContractDocument extends Model
{
    use SoftDeletes;

    protected $fillable = []; // every field is set exclusively via Actions — app/Actions/Contracts/**

    protected function casts(): array
    {
        return [
            'type' => ContractDocumentType::class,
            'status' => ContractDocumentStatus::class,
            'confidentiality' => DocumentConfidentiality::class,
            'generation_context' => 'array',
            'current_version' => 'integer',
            'founder_approved_at' => 'datetime',
            'investor_approved_at' => 'datetime',
            'legal_validated_at' => 'datetime',
            'signed_deposited_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<InvestmentContractWorkspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(InvestmentContractWorkspace::class, 'investment_contract_workspace_id');
    }

    /** @return HasMany<ContractDocumentVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(ContractDocumentVersion::class)->orderByDesc('version');
    }

    /** @return HasMany<ContractCondition, $this> */
    public function conditions(): HasMany
    {
        return $this->hasMany(ContractCondition::class, 'contract_document_id');
    }

    /** @return HasMany<ContractSignature, $this> */
    public function signatures(): HasMany
    {
        return $this->hasMany(ContractSignature::class, 'contract_document_id');
    }

    public function latestVersion(): ?ContractDocumentVersion
    {
        return $this->versions()->first();
    }

    public function hasBothApprovals(): bool
    {
        return $this->founder_approved_at !== null && $this->investor_approved_at !== null;
    }

    public function isLegalValidated(): bool
    {
        return $this->legal_validated_at !== null;
    }
}
