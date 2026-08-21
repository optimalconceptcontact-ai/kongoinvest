<?php

use App\Models\Sector;
use App\Models\User;
use Database\Seeders\MatchingWeightsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SectorsSeeder;
use Database\Seeders\StagesSeeder;
use Database\Seeders\SubscriptionPlansSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
    $this->seed(SectorsSeeder::class);
    $this->seed(StagesSeeder::class);
    $this->seed(MatchingWeightsSeeder::class);
});

it('never lets a non-admin reach any admin route', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();

    $this->actingAs($entrepreneur)->get('/admin')->assertForbidden();
    $this->actingAs($entrepreneur)->get('/admin/sectors')->assertForbidden();
    $this->actingAs($entrepreneur)->get('/admin/matching-weights')->assertForbidden();
});

it('lets an admin reach the admin dashboard with real stats', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get('/admin');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('stats.usersByRole'));
});

it('lets an admin manage sectors through the CRUD routes', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->post('/admin/sectors', ['name' => 'Nouveau Secteur'])
        ->assertSessionDoesntHaveErrors();

    expect(Sector::where('name', 'Nouveau Secteur')->exists())->toBeTrue();
});

it('rejects matching weights that do not sum to 100%', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $badWeights = collect(\App\Enums\MatchCriterion::cases())
        ->map(fn ($c) => ['criterion' => $c->value, 'weight' => 0.05]) // sums to 0.40, not 1.0
        ->all();

    $this->actingAs($admin)
        ->put('/admin/matching-weights', ['weights' => $badWeights])
        ->assertSessionHasErrors('weights');
});

it('accepts matching weights that sum to exactly 100%', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $goodWeights = collect(\App\Services\Matching\MatchingWeightsService::defaults())
        ->map(fn ($weight, $criterion) => ['criterion' => $criterion, 'weight' => $weight])
        ->values()
        ->all();

    $this->actingAs($admin)
        ->put('/admin/matching-weights', ['weights' => $goodWeights])
        ->assertSessionDoesntHaveErrors();
});
