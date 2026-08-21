<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionPlanFactory extends Factory
{
    protected $model = \App\Models\SubscriptionPlan::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
            'role_scope' => null,
            'price_minor' => 0,
            'currency' => 'USD',
            'billing_period' => 'monthly',
            'limits' => ['max_active_projects' => 1],
            'features' => [],
            'is_active' => true,
        ];
    }
}
