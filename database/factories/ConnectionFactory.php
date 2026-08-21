<?php

namespace Database\Factories;

use App\Enums\ConnectionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConnectionFactory extends Factory
{
    protected $model = \App\Models\Connection::class;

    public function definition(): array
    {
        $investorProfile = \App\Models\InvestorProfile::factory()->create();

        return [
            'project_id' => \App\Models\Project::factory()->published()->withTaxonomy(),
            'investor_profile_id' => $investorProfile->id,
            'requested_by_user_id' => $investorProfile->user_id,
            'status' => ConnectionStatus::Pending->value,
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn () => ['status' => ConnectionStatus::Accepted->value, 'responded_at' => now()]);
    }
}
