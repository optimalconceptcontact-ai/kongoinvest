<?php

use App\Models\AuditLog;
use App\Models\KycProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

it('lets a user submit KYC documents and moves status to submitted', function () {
    $user = User::factory()->entrepreneur()->create();
    $user->kycProfile()->create(['status' => 'draft']);

    // UploadedFile::fake()->create() produces an empty file in some
    // environments, which MediaLibrary rejects — a real minimal PDF
    // exercises the same production path deterministically.
    $path = tempnam(sys_get_temp_dir(), 'kyc').'.pdf';
    file_put_contents($path, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF");

    $this->actingAs($user)->post('/settings/kyc', [
        'files' => [new UploadedFile($path, 'id-card.pdf', 'application/pdf', null, true)],
    ])->assertSessionDoesntHaveErrors();

    expect($user->kycProfile->fresh()->status->value)->toBe('submitted');
});

it('never lets anyone but the owner or a kyc.review permission holder view a KYC profile', function () {
    $owner = User::factory()->entrepreneur()->create();
    $kyc = $owner->kycProfile()->create(['status' => 'submitted', 'submitted_at' => now()]);
    $kyc->forceFill(['status' => 'submitted', 'submitted_at' => now()])->save();
    $stranger = User::factory()->investor()->create();

    expect($stranger->can('view', $kyc))->toBeFalse();
    expect($owner->can('view', $kyc))->toBeTrue();
});

it('logs an audit entry when an admin approves or rejects a KYC profile', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $owner = User::factory()->entrepreneur()->create();
    $kyc = $owner->kycProfile()->create(['status' => 'submitted', 'submitted_at' => now()]);
    $kyc->forceFill(['status' => 'submitted', 'submitted_at' => now()])->save();

    $this->actingAs($admin)->post("/admin/kyc/{$kyc->id}/approve");

    expect($kyc->fresh()->status->value)->toBe('approved');
    expect(AuditLog::where('action', 'kyc.approved')->where('actor_id', $admin->id)->exists())->toBeTrue();
});

it('refuses KYC review to an admin holding admin.dashboard but not kyc.review', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Role::findByName('admin')->revokePermissionTo('kyc.review');

    $owner = User::factory()->entrepreneur()->create();
    $kyc = $owner->kycProfile()->create(['status' => 'submitted', 'submitted_at' => now()]);
    $kyc->forceFill(['status' => 'submitted', 'submitted_at' => now()])->save();

    expect($admin->fresh()->can('review', KycProfile::class))->toBeFalse();

    $this->actingAs($admin)->post("/admin/kyc/{$kyc->id}/approve")->assertForbidden();
    $this->actingAs($admin)->post("/admin/kyc/{$kyc->id}/reject", ['reason' => 'Document illisible.'])->assertForbidden();

    expect($kyc->fresh()->status->value)->toBe('submitted');
});
