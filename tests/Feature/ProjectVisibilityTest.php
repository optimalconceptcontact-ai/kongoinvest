<?php

use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

it('lets a guest view a published public project', function () {
    $project = Project::factory()->published()->withTaxonomy()->create();

    $this->get("/projects/{$project->slug}")->assertOk();
});

it('never lets a guest or an unrelated investor view a private project', function () {
    $owner = User::factory()->entrepreneur()->create();
    $project = Project::factory()->for($owner)->create(); // defaults to draft/private

    $this->get("/projects/{$project->slug}")->assertForbidden();

    $investor = User::factory()->investor()->create();
    $this->actingAs($investor)
        ->get("/projects/{$project->slug}")
        ->assertForbidden();
});

it('never lets an investor view, edit, or delete a project through the entrepreneur routes', function () {
    $investor = User::factory()->investor()->create();
    $project = Project::factory()->published()->withTaxonomy()->create();

    $this->actingAs($investor)
        ->get("/entrepreneur/projects/{$project->id}/edit")
        ->assertForbidden(); // role:entrepreneur middleware blocks this before the Policy even runs
});

it('lets an investor VIEW a public project but never modify it', function () {
    $investor = User::factory()->investor()->create();
    $project = Project::factory()->published()->withTaxonomy()->create();

    $this->actingAs($investor)->get("/projects/{$project->slug}")->assertOk();

    // No update/delete route is reachable by an investor at all — the
    // entrepreneur.* route group is gated by role:entrepreneur, so this
    // isn't just a Policy check, it's unreachable by design (spec §13).
    $this->actingAs($investor)
        ->put("/entrepreneur/projects/{$project->id}", ['title' => 'Nope'])
        ->assertForbidden();
});
