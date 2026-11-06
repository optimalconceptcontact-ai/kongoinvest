<?php

namespace App\Models;

use App\Enums\ContractConditionStatus;
use App\Enums\ContractConditionType;
use App\Enums\ContractPartyRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $investment_contract_workspace_id
 * @property int|null $contract_document_id
 * @property ContractConditionType $type
 * @property ContractPartyRole $responsible_party
 * @property string $description
 * @property Carbon|null $due_date
 * @property ContractConditionStatus $status
 * @property array<string, mixed>|null $source
 * @property Carbon|null $fulfilled_at
 * @property int|null $verified_by_user_id
 */
class ContractCondition extends Model
{
    use SoftDeletes;

    protected $fillable = []; // every field is set exclusively via Actions — app/Actions/Contracts/**

    protected function casts(): array
    {
        return [
            'type' => ContractConditionType::class,
            'responsible_party' => ContractPartyRole::class,
            'status' => ContractConditionStatus::class,
            'source' => 'array',
            'due_date' => 'date',
            'fulfilled_at' => 'datetime',
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

    /** @return BelongsTo<User, $this> */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    /** Le moteur d'audit signale toute obligation sans échéance (prompt §10). */
    public function hasDeadline(): bool
    {
        return $this->due_date !== null;
    }

    public function isBlocking(): bool
    {
        return $this->type === ContractConditionType::Suspensive
            && $this->status === ContractConditionStatus::Pending;
    }
}
