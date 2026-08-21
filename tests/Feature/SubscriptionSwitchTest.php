<?php

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\ProjectLimitService;
use App\Services\SubscriptionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

it('assigns the Free plan automatically at registration', function () {
    $this->post('/register', [
        'name' => 'Jean Mbala',
        'email' => 'jean@example.com',
        'password' => 'password123!',
        'password_confirmation' => 'password123!',
        'role' => 'entrepreneur',
    ]);

    $user = User::whereEmail('jean@example.com')->firstOrFail();
    $plan = app(SubscriptionService::class)->currentPlanFor($user);

    expect($plan->key)->toBe('free');
});

it('never lets an entrepreneur switch to the Investor plan, even via a direct request', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    app(SubscriptionService::class)->assignFreePlan($entrepreneur);

    $response = $this->actingAs($entrepreneur)->put('/settings/subscription', ['plan' => 'investor']);

    $response->assertSessionHasErrors('plan');
    expect(app(SubscriptionService::class)->currentPlanFor($entrepreneur)->key)->toBe('free');
});

it('lets an entrepreneur switch to the Entrepreneur plan and it actually changes their effective limits', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    app(SubscriptionService::class)->assignFreePlan($entrepreneur);

    $limits = app(ProjectLimitService::class);
    expect($limits->limitFor($entrepreneur))->toBe(1); // Free

    $this->actingAs($entrepreneur)->put('/settings/subscription', ['plan' => 'entrepreneur'])
        ->assertSessionDoesntHaveErrors();

    expect($limits->limitFor($entrepreneur->fresh()))->toBe(3); // Entrepreneur plan's max_active_projects
});

it('cancels the previous subscription row instead of deleting it when switching plans', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    $free = app(SubscriptionService::class)->assignFreePlan($entrepreneur);

    $entrepreneurPlan = SubscriptionPlan::where('key', 'entrepreneur')->firstOrFail();
    app(SubscriptionService::class)->switchPlan($entrepreneur, $entrepreneurPlan);

    expect($free->fresh()->status->value)->toBe('canceled');
    expect($entrepreneur->subscriptions()->count())->toBe(2); // history preserved, not overwritten
});
