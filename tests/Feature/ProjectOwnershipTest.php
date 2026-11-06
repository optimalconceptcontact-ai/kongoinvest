<?php

use App\Models\Project;
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
    $projectB = Project::factory()->for($entrepreneurB)->create();

    $this->actingAs($entrepreneurA)
        ->get("/entrepreneur/projects/{$projectB->slug}/edit")
        ->assertForbidden();

    $this->actingAs($entrepreneurA)
        ->put("/entrepreneur/projects/{$projectB->slug}", ['title' => 'Hijacked'])
        ->assertForbidden();

    $this->actingAs($entrepreneurA)
        ->delete("/entrepreneur/projects/{$projectB->slug}")
        ->assertForbidden();

    expect($projectB->fresh()->title)->not->toBe('Hijacked');
});

it('lets an entrepreneur manage their own project', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    $project = Project::factory()->for($entrepreneur)->create();

    $this->actingAs($entrepreneur)
        ->get("/entrepreneur/projects/{$project->slug}/edit")
        ->assertOk();
});
