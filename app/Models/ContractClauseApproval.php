<?php

namespace App\Models;

use App\Enums\ClauseDecision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $contract_clause_selection_id
 * @property int $user_id
 * @property ClauseDecision $decision
 * @property string|null $body
 * @property string|null $variant
 * @property string|null $comment
 * @property Carbon $decided_at
 */
class ContractClauseApproval extends Model
{
    protected $fillable = []; // append-only — every field is set exclusively via Actions

    protected function casts(): array
    {
        return [
            'decision' => ClauseDecision::class,
            'decided_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ContractClauseSelection, $this> */
    public function selection(): BelongsTo
    {
        return $this->belongsTo(ContractClauseSelection::class, 'contract_clause_selection_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
