<?php

use App\Enums\MatchCriterion;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Sector;
use App\Models\User;
use App\Services\Matching\MatchingWeightsService;
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
    $response->assertInertia(fn ($page) => $page
        ->has('stats.usersByRole')
        ->has('stats.investments')
        ->has('stats.revenue.byCurrency')
        ->where('stats.canViewStatistics', true));
});

it('counts only subscription revenue on the dashboard, never escrow investments', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $payer = User::factory()->investor()->create();

    Payment::factory()->create([
        'payer_id' => $payer->id,
        'purpose' => PaymentPurpose::Subscription->value,
        'status' => PaymentStatus::Succeeded->value,
        'amount_minor' => 2_900,
        'currency' => 'USD',
    ]);

    Payment::factory()->create([
        'payer_id' => $payer->id,
        'purpose' => PaymentPurpose::Subscription->value,
        'status' => PaymentStatus::Succeeded->value,
        'amount_minor' => 1_000,
        'currency' => 'CDF',
    ]);

    Payment::factory()->create([
        'payer_id' => $payer->id,
        'purpose' => PaymentPurpose::Subscription->value,
        'status' => PaymentStatus::Failed->value,
        'amount_minor' => 2_900,
        'currency' => 'USD',
    ]);

    Payment::factory()->create([
        'payer_id' => $payer->id,
        'purpose' => PaymentPurpose::Investment->value,
        'status' => PaymentStatus::Succeeded->value,
        'amount_minor' => 50_000,
        'currency' => 'USD',
    ]);

    $this->actingAs($admin)->get('/admin')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.revenue.succeededPayments', 2)
            ->where('stats.revenue.byCurrency.USD', fn ($value) => (float) $value === 29.0)
            ->where('stats.revenue.byCurrency.CDF', fn ($value) => (float) $value === 10.0));
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

    $badWeights = collect(MatchCriterion::cases())
        ->map(fn ($c) => ['criterion' => $c->value, 'weight' => 0.05]) // sums to 0.40, not 1.0
        ->all();

    $this->actingAs($admin)
        ->put('/admin/matching-weights', ['weights' => $badWeights])
        ->assertSessionHasErrors('weights');
});

it('accepts matching weights that sum to exactly 100%', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $goodWeights = collect(MatchingWeightsService::defaults())
        ->map(fn ($weight, $criterion) => ['criterion' => $criterion, 'weight' => $weight])
        ->values()
        ->all();

    $this->actingAs($admin)
        ->put('/admin/matching-weights', ['weights' => $goodWeights])
        ->assertSessionDoesntHaveErrors();
});
