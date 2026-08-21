<?php

namespace Database\Factories;

use App\Enums\KycStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class KycProfileFactory extends Factory
{
    protected $model = \App\Models\KycProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'status' => KycStatus::Draft->value,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn () => ['status' => KycStatus::Submitted->value, 'submitted_at' => now()]);
    }
}
