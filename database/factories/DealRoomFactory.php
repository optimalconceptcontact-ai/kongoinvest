<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DealRoomFactory extends Factory
{
    protected $model = \App\Models\DealRoom::class;

    public function definition(): array
    {
        $entrepreneur = \App\Models\User::factory()->create();
        $investor = \App\Models\User::factory()->create();
        $project = \App\Models\Project::factory()->for($entrepreneur)->published()->withTaxonomy()->create();
        $connection = \App\Models\Connection::factory()->accepted()->create([
            'project_id' => $project->id,
        ]);

        return [
            'connection_id' => $connection->id,
            'project_id' => $project->id,
            'entrepreneur_user_id' => $entrepreneur->id,
            'investor_user_id' => $investor->id,
            'status' => 'active',
        ];
    }
}
