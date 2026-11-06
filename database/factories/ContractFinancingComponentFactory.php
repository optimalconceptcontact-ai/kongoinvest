<?php

namespace Database\Factories;

use App\Enums\EquityMechanism;
use App\Enums\FinancingComponentType;
use App\Models\ContractFinancingComponent;
use App\Models\InvestmentContractWorkspace;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractFinancingComponentFactory extends Factory
{
    protected $model = ContractFinancingComponent::class;

    public function definition(): array
    {
        return [
            'investment_contract_workspace_id' => InvestmentContractWorkspace::factory(),
            'type' => FinancingComponentType::Capital->value,
            'equity_mechanism' => EquityMechanism::CapitalIncrease->value,
            'is_primary' => true,
            'amount_minor' => 10_000_000, // 100 000 USD — scénario §7
            'currency' => 'USD',
            'terms' => [],
        ];
    }

    /** Composante dette du scénario §7 : intérêts pas encore négociés. */
    public function debt(): static
    {
        return $this->state(fn () => [
            'type' => FinancingComponentType::Debt->value,
            'equity_mechanism' => null,
            'is_primary' => false,
            'sort_order' => 2,
            'amount_minor' => 15_000_000, // 150 000 USD — scénario §7
            'terms' => [
                'duration_months' => 48,
                'deferral_months' => 6, // intérêts et modalités restant à négocier
            ],
        ]);
    }

    /** Composante dette entièrement négociée : taux + échéancier. */
    public function debtWithTerms(int $rateBps, array $schedule): static
    {
        return $this->state(fn () => [
            'type' => FinancingComponentType::Debt->value,
            'equity_mechanism' => null,
            'is_primary' => false,
            'sort_order' => 2,
            'amount_minor' => 15_000_000, // 150 000 USD (principal)
            'terms' => [
                'rate_bps' => $rateBps,
                'duration_months' => 48,
                'deferral_months' => 6,
                'schedule' => $schedule,
            ],
        ]);
    }
}
