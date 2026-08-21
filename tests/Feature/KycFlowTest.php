<?php

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

it('lets a user submit KYC documents and moves status to submitted', function () {
    $user = User::factory()->entrepreneur()->create();
    $kyc = $user->kycProfile()->create(['status' => 'draft']);

    $this->actingAs($user)->post('/settings/kyc', [
        'files' => [UploadedFile::fake()->create('id-card.pdf', 500, 'application/pdf')],
    ])->assertSessionDoesntHaveErrors();

    expect($kyc->fresh()->status->value)->toBe('submitted');
});

it('never lets anyone but the owner or a kyc.review permission holder view a KYC profile', function () {
    $owner = User::factory()->entrepreneur()->create();
    $kyc = $owner->kycProfile()->create(['status' => 'submitted', 'submitted_at' => now()]);
    $stranger = User::factory()->investor()->create();

    expect($stranger->can('view', $kyc))->toBeFalse();
    expect($owner->can('view', $kyc))->toBeTrue();
});

it('logs an audit entry when an admin approves or rejects a KYC profile', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $owner = User::factory()->entrepreneur()->create();
    $kyc = $owner->kycProfile()->create(['status' => 'submitted', 'submitted_at' => now()]);

    $this->actingAs($admin)->post("/admin/kyc/{$kyc->id}/approve");

    expect($kyc->fresh()->status->value)->toBe('approved');
    expect(AuditLog::where('action', 'kyc.approved')->where('actor_id', $admin->id)->exists())->toBeTrue();
});
