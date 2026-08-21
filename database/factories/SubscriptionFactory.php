<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    protected $model = \App\Models\Subscription::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'subscription_plan_id' => \App\Models\SubscriptionPlan::factory(),
            'status' => SubscriptionStatus::Active->value,
            'starts_at' => now(),
        ];
    }
}
