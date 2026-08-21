<?php

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Spatie\Permission\Models\Role;

/**
 * Locks in the correction: "Professional" is a SubscriptionPlanKey, not
 * a UserRole. Exactly three roles exist — admin, entrepreneur, investor
 * — and the Professional plan is available to both entrepreneur AND
 * investor accounts, never a separate registrable persona.
 */
beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

it('seeds exactly three roles — admin, entrepreneur, investor — never a fourth "professional" role', function () {
    expect(Role::pluck('name')->sort()->values()->all())->toBe(['admin', 'entrepreneur', 'investor']);
});

it('never accepts "professional" as a registrable role', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'proftest@example.com',
        'password' => 'password123!',
        'password_confirmation' => 'password123!',
        'role' => 'professional',
    ]);

    $response->assertSessionHasErrors('role');
    expect(User::whereEmail('proftest@example.com')->exists())->toBeFalse();
});

it('lets an ENTREPRENEUR subscribe to the Professional plan', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    $professionalPlan = SubscriptionPlan::where('key', 'professional')->firstOrFail();

    $subscription = app(SubscriptionService::class)->switchPlan($entrepreneur, $professionalPlan);

    expect($subscription->plan->key)->toBe('professional');
});

it('lets an INVESTOR also subscribe to the very same Professional plan', function () {
    $investor = User::factory()->investor()->create();
    $professionalPlan = SubscriptionPlan::where('key', 'professional')->firstOrFail();

    $subscription = app(SubscriptionService::class)->switchPlan($investor, $professionalPlan);

    expect($subscription->plan->key)->toBe('professional');
});

it('the Professional plan is role-agnostic in the database, exactly like Free', function () {
    $professionalPlan = SubscriptionPlan::where('key', 'professional')->firstOrFail();

    expect($professionalPlan->role_scope)->toBeNull();
    expect($professionalPlan->isRoleAgnostic())->toBeTrue();
});
