<?php

use App\Enums\AmlAlertStatus;
use App\Enums\AmlAlertType;
use App\Enums\AmlRiskLevel;
use App\Enums\BusinessStatus;
use App\Enums\ComplianceCaseStatus;
use App\Enums\DocumentType;
use App\Enums\KycStatus;
use App\Enums\LegalForm;
use App\Enums\ProjectMaturityStage;
use App\Enums\SourceOfFundsCategory;
use App\Enums\UboControlNature;
use App\Models\AmlAlert;
use App\Models\BusinessVerification;
use App\Models\Company;
use App\Models\ComplianceAuditLog;
use App\Models\ComplianceCase;
use App\Models\KycDocument;
use App\Models\KycProfile;
use App\Models\Project;
use App\Models\SourceOfFundsDeclaration;
use App\Models\User;
use App\Services\Compliance\AmlScreeningService;
use App\Services\Compliance\BusinessVerificationService;
use App\Services\Compliance\ComplianceCaseManagementService;
use App\Services\Compliance\IdentityDocumentVerificationService;
use App\Services\Compliance\ProjectVerificationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
    Storage::fake('private');
});

function complianceHardeningPdf(string $name = 'identity.pdf'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'compliance_pdf').'.pdf';
    file_put_contents($path, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF");

    return new UploadedFile($path, $name, 'application/pdf', null, true);
}

function complianceHardeningKycPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Marie',
        'last_name' => 'Kabongo',
        'postnom' => 'Mbuyi',
        'date_of_birth' => '1990-05-12',
        'place_of_birth' => 'Kinshasa',
        'nationalities' => ['CD'],
        'residential_address' => '12 Avenue de la Paix, Gombe',
        'residential_country' => 'CD',
        'phone' => '+243810000000',
        'email' => 'marie.kabongo@example.com',
        'profession' => 'Consultante',
        'employer_or_activity' => 'Cabinet privé',
        'is_pep' => false,
        'is_acting_for_third_party' => false,
        'document_type' => DocumentType::DrcVoterCard->value,
        'issuing_country' => 'CD',
        'document_number' => 'CENI123456789KIN',
        'front_file' => complianceHardeningPdf(),
    ], $overrides);
}

function complianceHardeningBlockedCase(User $user, string $number = 'CASE-BLOCKED-1'): ComplianceCase
{
    $case = ComplianceCase::create([
        'case_number' => $number,
        'user_id' => $user->id,
        'subject_type' => User::class,
        'subject_id' => $user->id,
    ]);

    $case->forceFill([
        'status' => ComplianceCaseStatus::Blocked,
        'blocked_at' => now(),
    ])->save();

    return $case;
}

it('submits individual KYC over HTTP with private document, audit log and manual review only', function () {
    $user = User::factory()->entrepreneur()->create(['name' => 'Marie Kabongo']);

    $this->actingAs($user)
        ->post(route('settings.kyc.individual.submit'), complianceHardeningKycPayload())
        ->assertRedirect();

    $profile = KycProfile::where('user_id', $user->id)->firstOrFail();

    expect($profile->status)->toBe(KycStatus::Submitted)
        ->and($profile->submitted_at)->not->toBeNull()
        ->and($profile->compliance_case_id)->not->toBeNull();

    $document = KycDocument::where('kyc_profile_id', $profile->id)->firstOrFail();

    expect($document->status)->toBe('processing')
        ->and(Storage::disk('private')->exists($document->front_file_path))->toBeTrue();

    $verification = $profile->latestVerification;

    // OCR confidence is stored but never becomes an approval.
    expect($verification)->not->toBeNull()
        ->and($verification->status)->toBe('processing')
        ->and($verification->requires_manual_review)->toBeTrue()
        ->and($verification->overall_confidence_score)->toBeGreaterThan(0);

    $case = ComplianceCase::findOrFail($profile->compliance_case_id);

    expect($case->subject_type)->toBe(User::class)
        ->and($case->status)->toBe(ComplianceCaseStatus::AutomatedChecks);

    expect(ComplianceAuditLog::where('event', 'kyc.submitted')
        ->where('compliance_case_id', $case->id)
        ->exists())->toBeTrue();
});

it('refuses to clear a blocked compliance case by re-submitting KYC', function () {
    $user = User::factory()->entrepreneur()->create();
    KycProfile::factory()->create(['user_id' => $user->id]);
    $case = complianceHardeningBlockedCase($user);

    $this->actingAs($user)
        ->post(route('settings.kyc.individual.submit'), complianceHardeningKycPayload())
        ->assertStatus(423);

    expect($case->fresh()->status)->toBe(ComplianceCaseStatus::Blocked);
});

it('persists the submitted KYB status instead of silently dropping it', function () {
    $user = User::factory()->entrepreneur()->create();
    $company = Company::factory()->create(['name' => 'Kongo Tech Solutions SARL']);

    $verification = app(BusinessVerificationService::class)->initiateVerification($company, $user, [
        'company_name' => 'Kongo Tech Solutions SARL',
        'legal_form' => LegalForm::Sarl->value,
        'rccm_number' => 'CD/KIN/RCCM/24-B-00123',
        'tax_identification_number' => 'A1234567Z',
    ]);

    expect($verification->fresh()->status)->toBe('submitted')
        ->and($verification->complianceCase->status)->toBe(ComplianceCaseStatus::AutomatedChecks);

    expect(ComplianceAuditLog::where('event', 'kyb.submitted')
        ->where('subject_type', BusinessVerification::class)
        ->where('subject_id', $verification->id)
        ->exists())->toBeTrue();
});

it('forbids unrelated users from reading or graphing a KYB beneficial ownership file', function () {
    $owner = User::factory()->entrepreneur()->create();
    $outsider = User::factory()->investor()->create();
    $company = Company::factory()->create();

    $verification = app(BusinessVerificationService::class)->initiateVerification($company, $owner, [
        'company_name' => $company->name,
        'legal_form' => LegalForm::Sarl->value,
        'rccm_number' => 'CD/KIN/RCCM/24-B-77777',
    ]);

    $this->actingAs($outsider)
        ->get(route('ubo.index', $verification))
        ->assertForbidden();

    $this->actingAs($outsider)
        ->get(route('ubo.graph', $verification))
        ->assertForbidden();

    $this->actingAs($owner)
        ->get(route('ubo.index', $verification))
        ->assertOk();
});

it('never approves a blocked case or one with an open sanctions alert', function () {
    $officer = User::factory()->create();
    $service = app(ComplianceCaseManagementService::class);

    $blocked = complianceHardeningBlockedCase(User::factory()->investor()->create(), 'CASE-BLOCKED-2');

    expect(fn () => $service->approveCase($blocked, $officer, 'Dossier conforme'))
        ->toThrow(ValidationException::class);

    $case = ComplianceCase::create([
        'case_number' => 'CASE-SANCTION-OPEN',
        'user_id' => $officer->id,
        'subject_type' => User::class,
        'subject_id' => $officer->id,
    ]);

    AmlAlert::create([
        'compliance_case_id' => $case->id,
        'alert_type' => AmlAlertType::SanctionsMatch,
        'severity' => AmlRiskLevel::Critical,
        'status' => AmlAlertStatus::Open,
        'source' => 'test',
        'title' => 'Correspondance sanctions',
        'description' => 'Correspondance de nom à confirmer.',
    ]);

    expect(fn () => $service->approveCase($case, $officer, 'Dossier conforme'))
        ->toThrow(ValidationException::class);
});

it('keeps a blocked case blocked until every open sanctions alert is cleared and audits each resolution', function () {
    $officer = User::factory()->create();
    $case = complianceHardeningBlockedCase(User::factory()->investor()->create(), 'CASE-TWO-ALERTS');

    $first = AmlAlert::create([
        'compliance_case_id' => $case->id,
        'alert_type' => AmlAlertType::SanctionsMatch,
        'severity' => AmlRiskLevel::Critical,
        'status' => AmlAlertStatus::Open,
        'source' => 'test',
        'title' => 'Première homonymie',
        'description' => 'Homonymie à vérifier.',
    ]);

    $second = AmlAlert::create([
        'compliance_case_id' => $case->id,
        'alert_type' => AmlAlertType::SanctionsMatch,
        'severity' => AmlRiskLevel::Critical,
        'status' => AmlAlertStatus::Open,
        'source' => 'test',
        'title' => 'Deuxième homonymie',
        'description' => 'Autre homonymie à vérifier.',
    ]);

    $service = app(AmlScreeningService::class);

    $service->resolveAlert($first, 'false_positive', $officer, 'Homonymie documentée.');

    // One sanctions alert is still open: the case must remain blocked.
    expect($case->fresh()->status)->toBe(ComplianceCaseStatus::Blocked);

    expect(ComplianceAuditLog::where('event', 'aml.alert_resolved')
        ->where('subject_id', $first->id)
        ->exists())->toBeTrue();

    $service->resolveAlert($second, 'false_positive', $officer, 'Seconde homonymie documentée.');

    expect($case->fresh()->status)->toBe(ComplianceCaseStatus::ManualReview)
        ->and($case->fresh()->blocked_at)->toBeNull();
});

it('does not duplicate open alerts when screening is replayed', function () {
    $case = ComplianceCase::create([
        'case_number' => 'CASE-REPLAY',
        'user_id' => User::factory()->investor()->create()->id,
        'subject_type' => User::class,
        'subject_id' => 1,
    ]);

    $service = app(AmlScreeningService::class);
    $service->performScreening($case, 'Jean-Pierre Sanctioned Rebel', 'CD');
    $service->performScreening($case, 'Jean-Pierre Sanctioned Rebel', 'CD');

    expect($case->alerts()->where('alert_type', AmlAlertType::SanctionsMatch)->count())->toBe(1);
});

it('serves an identity document only to its owner through a signed URL', function () {
    $owner = User::factory()->entrepreneur()->create();
    $outsider = User::factory()->investor()->create();

    $profile = KycProfile::factory()->create(['user_id' => $owner->id]);

    $document = app(IdentityDocumentVerificationService::class)->verifyDocument(
        $profile,
        complianceHardeningPdf(),
        null,
        DocumentType::DrcVoterCard,
        'CD',
        'CENI123456789KIN',
    );

    $url = URL::temporarySignedRoute('kyc.documents.file', now()->addMinutes(5), ['document' => $document->id]);

    $this->actingAs($owner)->get($url)->assertOk();
    $this->actingAs($outsider)->get($url)->assertForbidden();
});

it('uses pre-incorporation data without erasing an existing incorporated status', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    $project = Project::factory()->create(['user_id' => $entrepreneur->id]);

    $project->forceFill(['business_status' => BusinessStatus::Incorporated])->save();

    app(ProjectVerificationService::class)->initializeProjectVerification(
        $project,
        ProjectMaturityStage::Growth,
        ['intended_company_name' => 'Kin Solar SARL', 'intended_legal_form' => LegalForm::Sarl->value]
    );

    $project->refresh();

    expect($project->maturity_stage)->toBe(ProjectMaturityStage::Growth)
        ->and($project->business_status)->toBe(BusinessStatus::Incorporated)
        ->and($project->formationReadiness?->intended_company_name)->toBe('Kin Solar SARL');
});

it('audits a source-of-funds declaration against its own record', function () {
    $user = User::factory()->investor()->create();

    $this->actingAs($user)
        ->post(route('settings.sof.store'), [
            'amount_minor' => 15000000,
            'currency' => 'USD',
            'funds_category' => SourceOfFundsCategory::BusinessDividends->value,
            'origin_description' => 'Dividendes 2024 de la société minière.',
            'bank_name' => 'Rawbank RDC',
            'bank_country' => 'CD',
        ])
        ->assertRedirect();

    $declaration = SourceOfFundsDeclaration::where('user_id', $user->id)->firstOrFail();

    $log = ComplianceAuditLog::where('event', 'sof.declaration_created')
        ->where('subject_type', SourceOfFundsDeclaration::class)
        ->where('subject_id', $declaration->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->actor_user_id)->toBe($user->id)
        ->and($log->legal_retention_required)->toBeTrue();
});

it('rejects a beneficial owner declaring more than 100 percent ownership', function () {
    $owner = User::factory()->entrepreneur()->create();
    $company = Company::factory()->create();

    $verification = app(BusinessVerificationService::class)->initiateVerification($company, $owner, [
        'company_name' => $company->name,
        'legal_form' => LegalForm::Sarl->value,
        'rccm_number' => 'CD/KIN/RCCM/24-B-88888',
    ]);

    $this->actingAs($owner)
        ->post(route('ubo.store', $verification), [
            'first_name' => 'Jean',
            'last_name' => 'Mukendi',
            'nationality' => 'CD',
            'country_of_residence' => 'CD',
            'is_pep' => false,
            'direct_ownership_bps' => 6000,
            'indirect_ownership_bps' => 6000,
            'control_nature' => UboControlNature::DirectEquity->value,
        ])
        ->assertSessionHasErrors('direct_ownership_bps');

    expect($verification->beneficialOwners()->count())->toBe(0);
});
