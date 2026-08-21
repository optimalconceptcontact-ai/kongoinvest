<?php

use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

it('never lets a Free-plan entrepreneur create a second active project', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    Project::factory()->for($entrepreneur)->create(); // one draft project already active

    $this->actingAs($entrepreneur)
        ->get('/entrepreneur/projects/create')
        ->assertForbidden();

    $this->actingAs($entrepreneur)
        ->post('/entrepreneur/projects', ['title' => 'Second project'])
        ->assertForbidden();
});

it('frees the project slot once the existing project is archived', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    $project = Project::factory()->for($entrepreneur)->create();

    $this->actingAs($entrepreneur)->post("/entrepreneur/projects/{$project->id}/archive");

    $this->actingAs($entrepreneur)
        ->get('/entrepreneur/projects/create')
        ->assertOk();
});
