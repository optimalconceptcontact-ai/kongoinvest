<?php

use App\Models\EntrepreneurProfile;
use App\Models\InvestorProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

it('assigns the chosen role and creates a matching profile on registration', function () {
    $this->post('/register', [
        'name' => 'Marie Kabongo',
        'email' => 'marie@example.com',
        'password' => 'password123!',
        'password_confirmation' => 'password123!',
        'role' => 'entrepreneur',
    ]);

    $user = User::whereEmail('marie@example.com')->firstOrFail();

    expect($user->hasRole('entrepreneur'))->toBeTrue();
    expect(EntrepreneurProfile::where('user_id', $user->id)->exists())->toBeTrue();
    expect(InvestorProfile::where('user_id', $user->id)->exists())->toBeFalse();
});

it('never allows self-registration as admin', function () {
    $response = $this->post('/register', [
        'name' => 'Wannabe Admin',
        'email' => 'admin-wannabe@example.com',
        'password' => 'password123!',
        'password_confirmation' => 'password123!',
        'role' => 'admin',
    ]);

    $response->assertSessionHasErrors('role');
    expect(User::whereEmail('admin-wannabe@example.com')->exists())->toBeFalse();
});
