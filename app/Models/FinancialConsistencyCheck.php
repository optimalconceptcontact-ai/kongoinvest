<?php

namespace App\Models;

use App\Enums\CheckSeverity;
use App\Enums\CheckStatus;
use App\Enums\FinancialCheckType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialConsistencyCheck extends Model
{
    protected $casts = [
        'type' => FinancialCheckType::class,
        'severity' => CheckSeverity::class,
        'status' => CheckStatus::class,
        'details' => 'array',
        'references' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(InvestmentContractWorkspace::class, 'investment_contract_workspace_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function blocksClosing(): bool
    {
        return $this->severity->blocksClosing() && $this->resolved_at === null;
    }
}
