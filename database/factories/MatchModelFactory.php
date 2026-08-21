<?php

namespace Database\Factories;

use App\Enums\MatchStatus;
use App\Models\InvestorProfile;
use App\Models\MatchModel;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class MatchModelFactory extends Factory
{
    protected $model = MatchModel::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory()->published()->withTaxonomy(),
            'investor_profile_id' => InvestorProfile::factory(),
            'score' => fake()->numberBetween(0, 100),
            'breakdown' => [],
            'status' => MatchStatus::Pending->value,
            'computed_at' => now(),
        ];
    }
}
