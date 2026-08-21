<?php

namespace Database\Factories;

use App\Enums\InvestorType;
use App\Enums\RiskAppetite;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvestorProfileFactory extends Factory
{
    protected $model = \App\Models\InvestorProfile::class;

    public function definition(): array
    {
        $min = fake()->numberBetween(5, 50) * 100000; // minor units
        return [
            'user_id' => \App\Models\User::factory(),
            'country_code' => fake()->countryCode(),
            'city' => fake()->city(),
            'investor_type' => fake()->randomElement(InvestorType::cases())->value,
            'risk_appetite' => fake()->randomElement(RiskAppetite::cases())->value,
            'ticket_min_minor' => $min,
            'ticket_max_minor' => $min * fake()->numberBetween(2, 10),
            'ticket_currency' => 'USD',
            'onboarding_completed' => true,
        ];
    }
}
