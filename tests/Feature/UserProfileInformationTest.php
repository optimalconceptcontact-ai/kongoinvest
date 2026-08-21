<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('updates profile information through the Fortify PUT route', function () {
    $user = User::factory()->entrepreneur()->create();

    $this->actingAs($user)->put('/user/profile-information', [
        'name' => 'Gloria Kabemba',
        'email' => $user->email,
        'phone' => '+243 812 345 678',
    ])->assertSessionHasNoErrors();

    expect($user->fresh()->only(['name', 'phone']))->toBe([
        'name' => 'Gloria Kabemba',
        'phone' => '+243 812 345 678',
    ]);
});

it('only accepts PUT on the Fortify profile route', function () {
    $user = User::factory()->entrepreneur()->create();

    // The Settings/Profile.tsx form must submit with `put()` — Fortify
    // registers /user/profile-information as PUT-only, PATCH is a 405.
    $this->actingAs($user)->patch('/user/profile-information', [
        'name' => 'Nope',
        'email' => $user->email,
    ])->assertStatus(405);

    expect($user->fresh()->name)->toBe($user->name);
});
