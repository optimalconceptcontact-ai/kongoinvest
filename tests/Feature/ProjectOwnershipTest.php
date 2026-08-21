<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

it('never allows entrepreneur A to modify entrepreneur B\'s project', function () {
    $entrepreneurA = User::factory()->entrepreneur()->create();
    $entrepreneurB = User::factory()->entrepreneur()->create();
    $projectB = \App\Models\Project::factory()->for($entrepreneurB)->create();

    $this->actingAs($entrepreneurA)
        ->get("/entrepreneur/projects/{$projectB->id}/edit")
        ->assertForbidden();

    $this->actingAs($entrepreneurA)
        ->put("/entrepreneur/projects/{$projectB->id}", ['title' => 'Hijacked'])
        ->assertForbidden();

    $this->actingAs($entrepreneurA)
        ->delete("/entrepreneur/projects/{$projectB->id}")
        ->assertForbidden();

    expect($projectB->fresh()->title)->not->toBe('Hijacked');
});

it('lets an entrepreneur manage their own project', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    $project = \App\Models\Project::factory()->for($entrepreneur)->create();

    $this->actingAs($entrepreneur)
        ->get("/entrepreneur/projects/{$project->id}/edit")
        ->assertOk();
});
