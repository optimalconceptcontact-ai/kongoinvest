<?php

namespace Database\Factories;

use App\Enums\ContractStatus;
use App\Enums\FundingType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractFactory extends Factory
{
    protected $model = \App\Models\Contract::class;

    public function definition(): array
    {
        return [
            'deal_room_id' => \App\Models\DealRoom::factory(),
            'generated_by_user_id' => \App\Models\User::factory(),
            'status' => ContractStatus::Generated->value,
            'investment_type' => FundingType::Equity->value,
            'amount_minor' => 10_000_00,
            'currency' => 'USD',
            'template_version' => 'v1',
        ];
    }
}
