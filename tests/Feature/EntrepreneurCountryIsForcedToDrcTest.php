<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

it('never lets an entrepreneur set their country away from the DRC', function () {
    $user = User::factory()->entrepreneur()->create();

    // Even though the form doesn't expose a `country_code` field at all,
    // simulate a malicious client sending one directly — it must be
    // silently ignored, not just hidden by the UI (spec §15, §100).
    $this->actingAs($user)->put('/settings/entrepreneur-profile', [
        'city' => 'Goma',
        'province' => 'nord_kivu',
        'country_code' => 'FR', // attempted override
    ])->assertSessionHasNoErrors();

    expect($user->entrepreneurProfile->fresh()->country_code)->toBe('CD');
});

it('forbids an investor from accessing the entrepreneur profile settings', function () {
    $investor = User::factory()->investor()->create();

    $this->actingAs($investor)
        ->get('/settings/entrepreneur-profile')
        ->assertForbidden();
});
