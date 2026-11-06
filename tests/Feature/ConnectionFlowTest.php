<?php

use App\Models\Connection;
use App\Models\DealRoom;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

it('lets an investor request a connection, and an entrepreneur accept it into a working deal room', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    $investor = User::factory()->investor()->create();
    $project = Project::factory()->for($entrepreneur)->published()->withTaxonomy()->create();

    $this->actingAs($investor)
        ->post("/investor/projects/{$project->id}/connections", ['message' => 'Intéressé par votre projet.'])
        ->assertSessionDoesntHaveErrors();

    $connection = Connection::firstOrFail();
    expect($connection->status->value)->toBe('pending');

    $this->actingAs($entrepreneur)
        ->post("/entrepreneur/connections/{$connection->id}/accept")
        ->assertRedirect();

    $connection->refresh();
    expect($connection->status->value)->toBe('accepted');

    $dealRoom = DealRoom::where('connection_id', $connection->id)->firstOrFail();
    expect($dealRoom->conversation)->not->toBeNull();

    // Both participants can now reach the deal room.
    $this->actingAs($entrepreneur)->get("/deal-rooms/{$dealRoom->id}")->assertOk();
    $this->actingAs($investor)->get("/deal-rooms/{$dealRoom->id}")->assertOk();
});

it('never lets an investor request a second connection while one is already pending or accepted', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    $investor = User::factory()->investor()->create();
    $project = Project::factory()->for($entrepreneur)->published()->withTaxonomy()->create();

    $this->actingAs($investor)->post("/investor/projects/{$project->id}/connections");

    $this->actingAs($investor)
        ->post("/investor/projects/{$project->id}/connections")
        ->assertSessionHasErrors('connection');

    expect(Connection::count())->toBe(1);
});

it('never lets an entrepreneur accept a connection request on someone else\'s project', function () {
    $entrepreneurA = User::factory()->entrepreneur()->create();
    $entrepreneurB = User::factory()->entrepreneur()->create();
    $investor = User::factory()->investor()->create();
    $projectB = Project::factory()->for($entrepreneurB)->published()->withTaxonomy()->create();

    $this->actingAs($investor)->post("/investor/projects/{$projectB->id}/connections");
    $connection = Connection::firstOrFail();

    $this->actingAs($entrepreneurA)
        ->post("/entrepreneur/connections/{$connection->id}/accept")
        ->assertForbidden();

    expect($connection->fresh()->status->value)->toBe('pending');
});
