<?php

namespace Database\Factories;

use App\Enums\DrcProvince;
use App\Enums\ProjectStatus;
use App\Enums\ProjectVisibility;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = \App\Models\Project::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory()->entrepreneur(),
            'title' => fake()->catchPhrase(),
            'description' => fake()->paragraph(),
            'provinces' => [fake()->randomElement(DrcProvince::cases())->value],
            'cities' => [fake()->city()],
            'funding_target_minor' => fake()->numberBetween(5, 500) * 100000,
            'funding_currency' => 'USD',
            'status' => ProjectStatus::Draft->value,
            'visibility' => ProjectVisibility::Private->value,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => ProjectStatus::Published->value,
            'visibility' => ProjectVisibility::Public->value,
            'published_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => ProjectStatus::Archived->value,
            'archived_at' => now(),
        ]);
    }

    /** Attaches one random active sector + stage after creation, so a factory project is always matchable/publishable out of the box. */
    public function withTaxonomy(): static
    {
        return $this->afterCreating(function (\App\Models\Project $project) {
            $sector = \App\Models\Sector::inRandomOrder()->first() ?? \App\Models\Sector::factory()->create();
            $stage = \App\Models\Stage::inRandomOrder()->first() ?? \App\Models\Stage::factory()->create();
            $project->sectors()->syncWithoutDetaching([$sector->id]);
            $project->stages()->syncWithoutDetaching([$stage->id]);
        });
    }
}
