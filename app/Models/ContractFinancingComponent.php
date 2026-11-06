<?php

namespace App\Models;

use App\Enums\EquityMechanism;
use App\Enums\FinancingComponentType;
use App\Enums\NegotiationTermStatus;
use Database\Factories\ContractFinancingComponentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $investment_contract_workspace_id
 * @property FinancingComponentType $type
 * @property EquityMechanism|null $equity_mechanism
 * @property string $currency
 * @property int $amount_minor
 * @property bool $is_primary
 * @property array<string, mixed>|null $terms
 * @property NegotiationTermStatus $status
 * @property int $sort_order
 */
class ContractFinancingComponent extends Model
{
    /** @use HasFactory<ContractFinancingComponentFactory> */
    use HasFactory;

    protected $fillable = []; // every field is set exclusively via Actions — app/Actions/Contracts/**

    protected function casts(): array
    {
        return [
            'type' => FinancingComponentType::class,
            'equity_mechanism' => EquityMechanism::class,
            'status' => NegotiationTermStatus::class,
            'is_primary' => 'boolean',
            'amount_minor' => 'integer',
            'terms' => 'array',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<InvestmentContractWorkspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(InvestmentContractWorkspace::class, 'investment_contract_workspace_id');
    }

    /**
     * Règle fondamentale (prompt §16, règle 5) : la dette ne
     * participe JAMAIS au calcul de dilution. Cette propriété
     * est le point d'ancrage déterministe du test.
     */
    public function confersCapitalEntry(): bool
    {
        return $this->type === FinancingComponentType::Capital
            || ($this->type === FinancingComponentType::Convertible
                && isset($this->terms['converts_to_equity'])
                && $this->terms['converts_to_equity'] === true);
    }

    public function isCapitalComponent(): bool
    {
        return $this->type === FinancingComponentType::Capital;
    }
}
