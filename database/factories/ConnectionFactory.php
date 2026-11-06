<?php

namespace Database\Factories;

use App\Enums\ConnectionStatus;
use App\Models\Connection;
use App\Models\InvestorProfile;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConnectionFactory extends Factory
{
    protected $model = Connection::class;

    public function definition(): array
    {
        $investorProfile = InvestorProfile::factory()->create();

        return [
            'project_id' => Project::factory()->published()->withTaxonomy(),
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
