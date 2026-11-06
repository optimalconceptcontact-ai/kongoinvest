<?php

namespace App\Models;

use App\Enums\ClauseCategory;
use App\Enums\ClauseDecision;
use App\Enums\ClauseOrigin;
use App\Enums\ClauseStatus;
use App\Enums\FinancingComponentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $investment_contract_workspace_id
 * @property int|null $contract_clause_library_id
 * @property int|null $library_version
 * @property int|null $proposed_by_user_id
 * @property string $title
 * @property string $body
 * @property ClauseCategory $category
 * @property ClauseOrigin $origin
 * @property ClauseStatus $status
 * @property FinancingComponentType|null $instrument_scope
 * @property string|null $variant
 * @property string|null $rationale
 * @property ClauseDecision|null $founder_decision
 * @property ClauseDecision|null $investor_decision
 * @property Carbon|null $founder_decided_at
 * @property Carbon|null $investor_decided_at
 * @property bool $is_custom
 * @property int $sort_order
 */
class ContractClauseSelection extends Model
{
    use SoftDeletes;

    protected $fillable = []; // every field is set exclusively via Actions — app/Actions/Contracts/**

    protected function casts(): array
    {
        return [
            'category' => ClauseCategory::class,
            'origin' => ClauseOrigin::class,
            'status' => ClauseStatus::class,
            'instrument_scope' => FinancingComponentType::class,
            'library_version' => 'integer',
            'founder_decision' => ClauseDecision::class,
            'investor_decision' => ClauseDecision::class,
            'founder_decided_at' => 'datetime',
            'investor_decided_at' => 'datetime',
            'is_custom' => 'boolean',
        ];
    }

    /** @return BelongsTo<InvestmentContractWorkspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(InvestmentContractWorkspace::class, 'investment_contract_workspace_id');
    }

    /** @return BelongsTo<ContractClauseLibrary, $this> */
    public function clauseLibrary(): BelongsTo
    {
        return $this->belongsTo(ContractClauseLibrary::class, 'contract_clause_library_id');
    }

    /** @return BelongsTo<User, $this> */
    public function proposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by_user_id');
    }

    /** @return HasMany<ContractClauseApproval, $this> */
    public function approvals(): HasMany
    {
        return $this->hasMany(ContractClauseApproval::class);
    }

    /**
     * Les deux validations requises sont enregistrées, datées et
     * acceptatives. Le statut dénormalisé ne fait jamais foi :
     * cette méthode est la règle (prompt §8).
     */
    public function isAcceptedByBoth(): bool
    {
        $founder = $this->approvals()
            ->where('decision', ClauseDecision::Accept->value)
            ->where('user_id', $this->workspace->dealRoom->entrepreneur_user_id)
            ->exists();

        $investor = $this->approvals()
            ->where('decision', ClauseDecision::Accept->value)
            ->where('user_id', $this->workspace->dealRoom->investor_user_id)
            ->exists();

        return $founder && $investor;
    }

    public function decisionOf(User $user): ?ClauseDecision
    {
        $approval = $this->approvals()->where('user_id', $user->id)->latest('decided_at')->first();

        return $approval?->decision;
    }
}
