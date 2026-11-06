<?php

namespace App\Models;

use App\Enums\AiRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $investment_contract_workspace_id
 * @property int|null $contract_negotiation_id
 * @property int $version
 * @property int|null $created_by_user_id
 * @property AiRequestStatus $status
 * @property array<string, mixed>|null $coverage
 * @property array<string, mixed>|null $synthesis
 * @property int|null $tokens_used
 * @property string|null $failure_reason
 * @property Carbon|null $completed_at
 */
class NegotiationConversationSnapshot extends Model
{
    protected $fillable = []; // every field is set exclusively via Actions / AI services — never user requests

    protected function casts(): array
    {
        return [
            'status' => AiRequestStatus::class,
            'coverage' => 'array',
            'synthesis' => 'array',
            'tokens_used' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<InvestmentContractWorkspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(InvestmentContractWorkspace::class, 'investment_contract_workspace_id');
    }

    /** @return BelongsTo<ContractNegotiation, $this> */
    public function negotiation(): BelongsTo
    {
        return $this->belongsTo(ContractNegotiation::class, 'contract_negotiation_id');
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Messages effectivement utilisés par cette analyse — la base
     * de toute traçabilité du prompt §4 / §14.
     *
     * @return list<int>
     */
    public function coveredMessageIds(): array
    {
        return data_get($this->coverage, 'message_ids', []);
    }
}
