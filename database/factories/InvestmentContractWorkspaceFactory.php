<?php

namespace Database\Factories;

use App\Enums\FinancingCategory;
use App\Enums\FounderControlPreference;
use App\Models\DealRoom;
use App\Models\InvestmentContractWorkspace;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvestmentContractWorkspaceFactory extends Factory
{
    protected $model = InvestmentContractWorkspace::class;

    public function definition(): array
    {
        return [
            'deal_room_id' => DealRoom::factory(),
            // Rôle des participants porté par la DealRoom — le
            // créateur n'est qu'une trace d'audit.
            'created_by_user_id' => User::factory(),
            'status' => 'negotiation',
            'currency' => 'USD',
        ];
    }

    /** L'espace est porté par l'entreprise donnée (multi-opérations). */
    public function forCompany(string|int $companyId): static
    {
        return $this->state(fn () => ['company_id' => (string) $companyId]);
    }

    /**
     * Scénario de référence §7 : SARLU, financement mixte, fondateur
     * flexible — pré-money 300 000 USD, 100 000 USD en capital,
     * 150 000 USD en dette (48 mois, différé 6 mois, intérêts à
     * négocier), 25 % investisseur / 75 % fondateur.
     */
    public function mixedSarluReference(): static
    {
        return $this->state(fn () => [
            'financing_category' => FinancingCategory::Mixed->value,
            'founder_control_preference' => FounderControlPreference::Flexible->value,
            'pre_money_minor' => 30_000_000,
            'post_money_minor' => 40_000_000,
            'total_investment_minor' => 25_000_000,
            'founder_share_bps' => 7500,
            'investor_share_bps' => 2500,
        ]);
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
