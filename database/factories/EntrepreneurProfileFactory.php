<?php

namespace Database\Factories;

use App\Enums\DrcProvince;
use App\Models\EntrepreneurProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EntrepreneurProfileFactory extends Factory
{
    protected $model = EntrepreneurProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'job_title' => fake()->jobTitle(),
            'bio' => fake()->paragraph(),
            'province' => fake()->randomElement(DrcProvince::cases())->value,
            'city' => fake()->city(),
            'years_experience' => fake()->numberBetween(0, 25),
            'onboarding_completed' => true,
        ];
    }
}
