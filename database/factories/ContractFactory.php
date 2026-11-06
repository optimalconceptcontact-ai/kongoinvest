<?php

namespace Database\Factories;

use App\Enums\ContractStatus;
use App\Enums\FundingType;
use App\Models\Contract;
use App\Models\DealRoom;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        return [
            'deal_room_id' => DealRoom::factory(),
            'generated_by_user_id' => User::factory(),
            'status' => ContractStatus::Generated->value,
            'investment_type' => FundingType::Equity->value,
            'amount_minor' => 10_000_00,
            'currency' => 'USD',
            'template_version' => 'v1',
        ];
    }
}
