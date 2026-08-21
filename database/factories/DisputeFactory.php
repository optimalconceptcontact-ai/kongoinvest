<?php

namespace Database\Factories;

use App\Enums\DisputeStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class DisputeFactory extends Factory
{
    protected $model = \App\Models\Dispute::class;

    public function definition(): array
    {
        return [
            'escrow_id' => \App\Models\Escrow::factory(),
            'raised_by_user_id' => \App\Models\User::factory(),
            'status' => DisputeStatus::Opened->value,
            'reason' => fake()->sentence(),
        ];
    }
}
