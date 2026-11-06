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

function acceptedDealRoom(): array
{
    $entrepreneur = User::factory()->entrepreneur()->create();
    $investor = User::factory()->investor()->create();
    $project = Project::factory()->for($entrepreneur)->published()->withTaxonomy()->create();

    test()->actingAs($investor)->post("/investor/projects/{$project->id}/connections");
    $connection = Connection::firstOrFail();
    test()->actingAs($entrepreneur)->post("/entrepreneur/connections/{$connection->id}/accept");

    $dealRoom = DealRoom::where('connection_id', $connection->id)->firstOrFail();

    return [$dealRoom, $entrepreneur, $investor];
}

it('spec §135: a user outside the Deal Room cannot read its messages', function () {
    [$dealRoom, $entrepreneur, $investor] = acceptedDealRoom();
    $outsider = User::factory()->investor()->create();

    $this->actingAs($entrepreneur)->post("/deal-rooms/{$dealRoom->id}/messages", ['body' => 'Bonjour, discutons du projet.']);

    $this->actingAs($outsider)
        ->get("/deal-rooms/{$dealRoom->id}")
        ->assertForbidden();

    $this->actingAs($outsider)
        ->post("/deal-rooms/{$dealRoom->id}/messages", ['body' => 'Je ne devrais pas pouvoir écrire ici.'])
        ->assertForbidden();
});

it('lets both participants exchange messages and see them in order', function () {
    [$dealRoom, $entrepreneur, $investor] = acceptedDealRoom();

    $this->actingAs($investor)->post("/deal-rooms/{$dealRoom->id}/messages", ['body' => 'Bonjour !']);
    $this->actingAs($entrepreneur)->post("/deal-rooms/{$dealRoom->id}/messages", ['body' => 'Bonjour, merci de votre intérêt.']);

    $response = $this->actingAs($investor)->get("/deal-rooms/{$dealRoom->id}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('dealRoom.messages', 2));
});

it('never lets a rejected connection produce a deal room at all', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    $investor = User::factory()->investor()->create();
    $project = Project::factory()->for($entrepreneur)->published()->withTaxonomy()->create();

    $this->actingAs($investor)->post("/investor/projects/{$project->id}/connections");
    $connection = Connection::firstOrFail();

    $this->actingAs($entrepreneur)->post("/entrepreneur/connections/{$connection->id}/reject");

    expect(DealRoom::count())->toBe(0);
});
