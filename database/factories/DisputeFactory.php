<?php

namespace Database\Factories;

use App\Enums\DisputeStatus;
use App\Models\Dispute;
use App\Models\Escrow;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DisputeFactory extends Factory
{
    protected $model = Dispute::class;

    public function definition(): array
    {
        return [
            'escrow_id' => Escrow::factory(),
            'raised_by_user_id' => User::factory(),
            'status' => DisputeStatus::Opened->value,
            'reason' => fake()->sentence(),
        ];
    }
}
