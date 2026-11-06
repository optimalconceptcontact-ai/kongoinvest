<?php

namespace App\Models;

use App\Enums\NegotiationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractNegotiation extends Model
{
    protected $fillable = []; // every field is set exclusively via Actions — app/Actions/Contracts/**

    protected function casts(): array
    {
        return [
            'status' => NegotiationStatus::class,
            'agreed_conditions' => 'array',
            'opened_at' => 'datetime',
            'concluded_at' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(InvestmentContractWorkspace::class, 'investment_contract_workspace_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(NegotiationConversationSnapshot::class);
    }
}
