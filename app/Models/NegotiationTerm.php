<?php

namespace App\Models;

use App\Enums\FinancingComponentType;
use App\Enums\NegotiationTermStatus;
use App\Enums\TermCategory;
use App\Enums\TermKind;
use App\Enums\TermSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $investment_contract_workspace_id
 * @property int|null $snapshot_id
 * @property TermSource $source
 * @property string $subject
 * @property string $label
 * @property TermCategory $category
 * @property TermKind $kind
 * @property FinancingComponentType|null $instrument
 * @property NegotiationTermStatus $status
 * @property array<string, mixed>|null $value
 * @property string|null $display_value
 * @property int|null $conflict_with_term_id
 * @property array<string, mixed>|null $source_context
 * @property string|null $notes
 * @property int|null $proposed_by_user_id
 * @property int|null $source_message_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class NegotiationTerm extends Model
{
    protected $fillable = []; // every field is set exclusively via Actions / extraction services

    protected function casts(): array
    {
        return [
            'category' => TermCategory::class,
            'kind' => TermKind::class,
            'instrument' => FinancingComponentType::class,
            'status' => NegotiationTermStatus::class,
            'source' => TermSource::class,
            'value' => 'array',
            'source_context' => 'array',
        ];
    }

    /** @return BelongsTo<InvestmentContractWorkspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(InvestmentContractWorkspace::class, 'investment_contract_workspace_id');
    }

    /** @return BelongsTo<NegotiationConversationSnapshot, $this> */
    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(NegotiationConversationSnapshot::class, 'snapshot_id');
    }

    /** @return BelongsTo<Message, $this> */
    public function sourceMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'source_message_id');
    }

    /** @return BelongsTo<self, $this> */
    public function conflictWithTerm(): BelongsTo
    {
        return $this->belongsTo(self::class, 'conflict_with_term_id');
    }

    /** @return BelongsTo<User, $this> */
    public function proposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by_user_id');
    }

    /**
     * Une discussion n'est pas un accord : une condition non
     * explicitement acceptée / formalisée ne peut jamais
     * contractualiser automatiquement (prompt §4, §16 règle 3).
     */
    public function isBinding(): bool
    {
        return in_array($this->status, [NegotiationTermStatus::ExplicitlyAccepted, NegotiationTermStatus::Formalized], true);
    }

    public function isContradictory(): bool
    {
        return $this->status === NegotiationTermStatus::Contradictory
            || $this->conflict_with_term_id !== null;
    }
}
