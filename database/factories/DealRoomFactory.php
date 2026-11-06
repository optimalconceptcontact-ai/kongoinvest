<?php

namespace Database\Factories;

use App\Models\Connection;
use App\Models\DealRoom;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DealRoomFactory extends Factory
{
    protected $model = DealRoom::class;

    public function definition(): array
    {
        $entrepreneur = User::factory()->create();
        $investor = User::factory()->create();
        $project = Project::factory()->for($entrepreneur)->published()->withTaxonomy()->create();
        $connection = Connection::factory()->accepted()->create([
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
