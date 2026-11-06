<?php

namespace App\Models;

use App\Enums\CheckSeverity;
use App\Enums\CheckStatus;
use App\Enums\LegalCheckType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $investment_contract_workspace_id
 * @property string $batch
 * @property LegalCheckType $type
 * @property CheckSeverity $severity
 * @property CheckStatus $status
 * @property string $title
 * @property string $message
 * @property array<string, mixed>|null $details
 * @property array<string, mixed>|null $references
 * @property int|null $resolved_by_user_id
 * @property Carbon|null $resolved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class LegalComplianceCheck extends Model
{
    protected $fillable = []; // every field is set exclusively via trusted Services / Actions

    protected function casts(): array
    {
        return [
            'type' => LegalCheckType::class,
            'severity' => CheckSeverity::class,
            'status' => CheckStatus::class,
            'details' => 'array',
            'references' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<InvestmentContractWorkspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(InvestmentContractWorkspace::class, 'investment_contract_workspace_id');
    }

    /** @return BelongsTo<User, $this> */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function blocksClosing(): bool
    {
        return $this->severity->blocksClosing() && $this->resolved_at === null;
    }
}
