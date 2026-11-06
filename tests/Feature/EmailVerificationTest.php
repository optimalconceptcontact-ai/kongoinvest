<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

it('redirects unverified users away from verified routes', function () {
    $user = User::factory()->unverified()->entrepreneur()->create();

    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
});

it('lets verified users reach the dashboard', function () {
    $user = User::factory()->entrepreneur()->create();

    $this->actingAs($user)->get('/dashboard')->assertOk();
});
