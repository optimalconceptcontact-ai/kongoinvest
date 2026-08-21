<?php

namespace Database\Factories;

use App\Enums\AiRequestStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiAnalysisFactory extends Factory
{
    protected $model = \App\Models\AiAnalysis::class;

    public function definition(): array
    {
        return [
            'project_id' => \App\Models\Project::factory(),
            'requested_by_user_id' => \App\Models\User::factory(),
            'status' => AiRequestStatus::Completed->value,
            'provider' => 'anthropic',
            'investment_score' => fake()->numberBetween(40, 90),
            'content' => [
                'subScores' => ['market' => 70, 'team' => 65, 'traction' => 50, 'businessModel' => 60, 'scalability' => 55, 'risk' => 60, 'financialPotential' => 58],
                'executiveSummary' => fake()->paragraph(),
                'strengths' => [fake()->sentence()],
                'weaknesses' => [fake()->sentence()],
                'risks' => [fake()->sentence()],
                'redFlags' => [],
                'dueDiligenceQuestions' => [fake()->sentence()],
                'recommendation' => fake()->sentence(),
                'disclaimer' => \App\Services\Ai\ProjectAnalysisService::DISCLAIMER,
            ],
        ];
    }
}
