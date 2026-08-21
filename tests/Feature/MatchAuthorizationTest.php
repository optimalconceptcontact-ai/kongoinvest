<?php

use App\Models\InvestorProfile;
use App\Models\MatchModel;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

it('never lets investor A act on investor B\'s match', function () {
    $investorA = User::factory()->investor()->create();
    $investorB = User::factory()->investor()->create();

    $match = MatchModel::factory()->create([
        'investor_profile_id' => InvestorProfile::where('user_id', $investorB->id)->value('id'),
    ]);

    $this->actingAs($investorA)
        ->post("/investor/matches/{$match->id}/dismiss")
        ->assertForbidden();
});

it('scopes the investor matches index to the authenticated investor only', function () {
    $investorA = User::factory()->investor()->create();
    $investorB = User::factory()->investor()->create();

    MatchModel::factory()->create(['investor_profile_id' => InvestorProfile::where('user_id', $investorA->id)->value('id')]);
    MatchModel::factory()->create(['investor_profile_id' => InvestorProfile::where('user_id', $investorB->id)->value('id')]);

    $response = $this->actingAs($investorA)->get('/investor/matches');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('matches', 1));
});

it('scopes the entrepreneur matches index to their own projects only', function () {
    $entrepreneurA = User::factory()->entrepreneur()->create();
    $entrepreneurB = User::factory()->entrepreneur()->create();

    $projectA = Project::factory()->for($entrepreneurA)->create();
    $projectB = Project::factory()->for($entrepreneurB)->create();

    MatchModel::factory()->create(['project_id' => $projectA->id]);
    MatchModel::factory()->create(['project_id' => $projectB->id]);

    $response = $this->actingAs($entrepreneurA)->get('/entrepreneur/matches');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('matches', 1));
});
