<?php

namespace Database\Factories;

use App\Enums\KycStatus;
use App\Models\KycProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class KycProfileFactory extends Factory
{
    protected $model = KycProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => KycStatus::Draft->value,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn () => ['status' => KycStatus::Submitted->value, 'submitted_at' => now()]);
    }
}
