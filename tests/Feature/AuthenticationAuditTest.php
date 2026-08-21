<?php

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

it('audits every login, closing the gap spec §49 explicitly named', function () {
    $user = User::factory()->entrepreneur()->create(['password' => bcrypt('password123!')]);

    $this->post('/login', ['email' => $user->email, 'password' => 'password123!']);

    expect(AuditLog::where('action', 'auth.login')->where('actor_id', $user->id)->exists())->toBeTrue();
});

it('audits logout', function () {
    $user = User::factory()->entrepreneur()->create();

    $this->actingAs($user)->post('/logout');

    expect(AuditLog::where('action', 'auth.logout')->where('actor_id', $user->id)->exists())->toBeTrue();
});
