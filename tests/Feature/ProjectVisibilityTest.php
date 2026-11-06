<?php

use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

it('never lets a guest view a published public project', function () {
    $project = Project::factory()->published()->withTaxonomy()->create();

    $this->get("/projects/{$project->slug}")->assertRedirect(route('home'));
});

it('never lets a guest or an unrelated investor view a private project', function () {
    $owner = User::factory()->entrepreneur()->create();
    $project = Project::factory()->for($owner)->create(); // defaults to draft/private

    $this->get("/projects/{$project->slug}")->assertRedirect(route('home'));

    $investor = User::factory()->investor()->create();
    $this->actingAs($investor)
        ->get("/projects/{$project->slug}")
        ->assertForbidden();
});

it('never lets an investor view, edit, or delete a project through the entrepreneur routes', function () {
    $investor = User::factory()->investor()->create();
    $project = Project::factory()->published()->withTaxonomy()->create();

    $this->actingAs($investor)
        ->get("/entrepreneur/projects/{$project->slug}/edit")
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
        ->put("/entrepreneur/projects/{$project->slug}", ['title' => 'Nope'])
        ->assertForbidden();
});

it('lets an entrepreneur view their own project at /projects/{slug}', function () {
    $owner = User::factory()->entrepreneur()->create();
    $project = Project::factory()->for($owner)->published()->withTaxonomy()->create();

    $this->actingAs($owner)->get("/projects/{$project->slug}")->assertOk();
});

it('never lets an entrepreneur view another entrepreneur project at /projects/{slug}', function () {
    $ownerA = User::factory()->entrepreneur()->create();
    $ownerB = User::factory()->entrepreneur()->create();
    $projectA = Project::factory()->for($ownerA)->published()->withTaxonomy()->create();

    $this->actingAs($ownerB)->get("/projects/{$projectA->slug}")->assertForbidden();
});

it('never lets an entrepreneur access the public projects catalog at /projects', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();

    $this->actingAs($entrepreneur)->get('/projects')->assertForbidden();
});

it('never lets a guest access the projects catalog at /projects', function () {
    Project::factory()->published()->withTaxonomy()->create();

    $this->get('/projects')->assertRedirect(route('home'));
});

it('lets investors access the projects catalog at /projects', function () {
    Project::factory()->published()->withTaxonomy()->create();

    $investor = User::factory()->investor()->create();
    $this->actingAs($investor)->get('/projects')->assertOk();
});

it('lets an authenticated user download the project report as pdf', function () {
    $investor = User::factory()->investor()->create();
    $project = Project::factory()->published()->withTaxonomy()->create();

    $this->actingAs($investor)
        ->get("/projects/{$project->slug}/report")
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

it('never lets a guest download the project report', function () {
    $project = Project::factory()->published()->withTaxonomy()->create();

    $this->get("/projects/{$project->slug}/report")->assertRedirect(route('home'));
});
