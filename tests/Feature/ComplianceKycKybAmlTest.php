<?php

use App\Enums\AmlAlertStatus;
use App\Enums\AmlAlertType;
use App\Enums\AmlRiskLevel;
use App\Enums\BusinessLegalDocumentType;
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
use App\Models\KycProfile;
use App\Models\Project;
use App\Models\SourceOfFundsDeclaration;
use App\Models\SourceOfWealthDeclaration;
use App\Models\User;
use App\Services\Compliance\AmlRiskAssessmentService;
use App\Services\Compliance\AmlScreeningService;
use App\Services\Compliance\BeneficialOwnershipService;
use App\Services\Compliance\BusinessVerificationService;
use App\Services\Compliance\CompanyFormationReadinessService;
use App\Services\Compliance\ComplianceCaseManagementService;
use App\Services\Compliance\IdentityDocumentVerificationService;
use App\Services\Compliance\InvestmentComplianceGateService;
use App\Services\Compliance\PartnerBankComplianceService;
use App\Services\Compliance\ProjectVerificationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

// Helper for minimal PDF file upload
function createTestPdf(): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'test_kyc').'.pdf';
    file_put_contents($path, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF");

    return new UploadedFile($path, 'test-doc.pdf', 'application/pdf', null, true);
}

// Helper for minimal Image file upload
function createTestImage(): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'test_selfie').'.jpg';
    file_put_contents($path, 'fake_jpg_content');

    return new UploadedFile($path, 'selfie.jpg', 'image/jpeg', null, true);
}

it('verifies individual KYC documents with separate OCR confidence scores and never auto-approves on score alone', function () {
    $user = User::factory()->entrepreneur()->create();
    $profile = KycProfile::create([
        'user_id' => $user->id,
        'first_name' => 'Jean-Luc',
        'last_name' => 'Mukendi',
        'postnom' => 'Kalonji',
        'residential_country' => 'CD',
    ]);

    $service = app(IdentityDocumentVerificationService::class);
    $file = createTestPdf();

    $document = $service->verifyDocument(
        $profile,
        $file,
        null,
        DocumentType::DrcVoterCard,
        'CD',
        'CENI-123456789-KIN',
        '2023-01-15',
        null
    );

    expect($document->exists)->toBeTrue();
    expect($document->checks()->count())->toBe(1);

    $check = $document->latestCheck;
    expect($check->overall_ocr_score)->toBeGreaterThan(0.85);
    // CRITICAL ACCEPTANCE CRITERIA: High OCR score (> 0.85) DOES NOT automatically approve the KYC profile!
    expect($profile->fresh()->status)->not->toBe(KycStatus::Approved);
    expect($check->field_confidence_scores)->toBeArray();
    expect($check->field_confidence_scores)->toHaveKey('first_name');
    expect($check->field_confidence_scores)->toHaveKey('document_number');
});

it('verifies biometrics with liveness detection and facial matching against document', function () {
    $user = User::factory()->investor()->create();
    $profile = KycProfile::create(['user_id' => $user->id]);

    $service = app(IdentityDocumentVerificationService::class);
    $selfie = createTestImage();

    $check = $service->verifyBiometrics($profile, $selfie);

    expect($check->exists)->toBeTrue();
    expect($check->liveness_passed)->toBeTrue();
    expect($check->face_match_passed)->toBeTrue();
    expect($check->liveness_score)->toBeGreaterThan(0.9);
});

it('manages business KYB under OHADA legal forms with document coherence assessments', function () {
    $user = User::factory()->entrepreneur()->create();
    $company = Company::factory()->create([
        'name' => 'Kongo Tech Solutions SARL',
        'registration_number' => 'CD/KIN/RCCM/24-B-00123',
        'legal_form' => 'sarl',
    ]);

    $kybService = app(BusinessVerificationService::class);

    $verification = $kybService->initiateVerification($company, $user, [
        'company_name' => 'Kongo Tech Solutions SARL',
        'legal_form' => LegalForm::Sarl->value,
        'rccm_number' => 'CD/KIN/RCCM/24-B-00123',
        'tax_identification_number' => 'A1234567Z',
        'registered_office_address' => '15 Boulevard du 30 Juin, Gombe, Kinshasa',
        'share_capital_minor' => 5000000, // $50,000 USD
        'share_capital_currency' => 'USD',
        'industry_sector' => 'Technologies & Télécoms',
        'legal_representative_name' => $user->name,
    ]);

    expect($verification->exists)->toBeTrue();
    expect($verification->complianceCase)->not->toBeNull();
    expect($verification->complianceCase->subject_type)->toBe(Company::class);

    // Attach RCCM document
    $doc = $kybService->attachDocument(
        $verification,
        createTestPdf(),
        BusinessLegalDocumentType::Rccm,
        'Extrait RCCM Greffe Kinshasa'
    );

    expect($doc->exists)->toBeTrue();
    expect($doc->is_verified)->toBeFalse();

    // Coherence evaluation
    $coherence = $kybService->assessDocumentCoherence($verification);
    expect($coherence['missing_mandatory_docs'])->toContain('statutes');
});

it('calculates beneficial ownership (UBO) recursively across complex multi-tier holding chains and builds graph', function () {
    $company = Company::factory()->create(['name' => 'Congo Mining Ventures SA']);
    $verification = BusinessVerification::create([
        'company_id' => $company->id,
        'company_name' => $company->name,
        'legal_form' => LegalForm::Sa,
        'share_capital_minor' => 100000000,
        'share_capital_currency' => 'USD',
    ]);

    $uboService = app(BeneficialOwnershipService::class);

    // Multi-tier chain calculation:
    // Person A holds 60% of Holding B, Holding B holds 50% of Target Company
    // Effective indirect percentage = 60% * 50% = 30% (3000 bps)
    $effectiveBps = $uboService->computeMultiTierPercentage([6000, 5000]);
    expect($effectiveBps)->toBe(3000);

    // Record UBO Person
    $ubo = $uboService->declareBeneficialOwner($verification, [
        'first_name' => 'Albert',
        'last_name' => 'Yuma',
        'postnom' => 'Kenga',
        'nationality' => 'CD',
        'country_of_residence' => 'CD',
        'direct_ownership_bps' => 1000, // 10%
        'indirect_ownership_bps' => 2000, // 20%
        'voting_rights_bps' => 3000, // 30%
        'control_nature' => UboControlNature::IndirectEquity,
        'is_pep' => true,
        'pep_details' => 'Ancien PCA Gecamines',
    ]);

    expect($ubo->totalEffectivePercentage())->toBe(30.0);
    // Standard OHADA threshold is 25% => qualified UBO!
    expect($ubo->meets_statutory_threshold)->toBeTrue();

    // Record relationship link
    $link = $uboService->recordOwnershipLink($verification, [
        'parent_entity_type' => 'legal_entity',
        'parent_name' => 'Central Africa Mining Holding SAS',
        'parent_country' => 'CD',
        'child_entity_name' => $company->name,
        'tier_level' => 1,
        'equity_percentage_bps' => 5000,
    ]);

    expect($link->exists)->toBeTrue();

    // Verify graph structure
    $graph = $uboService->buildOwnershipGraph($verification);
    expect($graph['nodes'])->not->toBeEmpty();
    expect($graph['edges'])->not->toBeEmpty();
});

it('triggers AML screening alerts on PEP or sanctions matches and blocks critical cases', function () {
    $user = User::factory()->investor()->create();
    $case = ComplianceCase::create([
        'case_number' => 'CASE-TEST-001',
        'user_id' => $user->id,
        'subject_type' => User::class,
        'subject_id' => $user->id,
        'status' => ComplianceCaseStatus::AutomatedChecks,
    ]);

    $screeningService = app(AmlScreeningService::class);

    // Screening with simulated sanctions hit
    $result = $screeningService->performScreening(
        $case,
        'Jean-Pierre Sanctioned Rebel',
        'CD',
        'individual'
    );

    expect($result['sanctions']->has_match)->toBeTrue();
    // Critical case: must be blocked immediately
    expect($case->fresh()->status)->toBe(ComplianceCaseStatus::Blocked);
    expect($case->fresh()->risk_level)->toBe(AmlRiskLevel::Critical);
    expect($case->fresh()->isBlockedOrSuspended())->toBeTrue();
});

it('allows compliance officers to investigate and resolve false positives', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->investor()->create();
    $case = ComplianceCase::create([
        'case_number' => 'CASE-TEST-002',
        'user_id' => $user->id,
        'subject_type' => User::class,
        'subject_id' => $user->id,
        'status' => ComplianceCaseStatus::Blocked,
        'risk_level' => AmlRiskLevel::Critical,
    ]);

    $screeningService = app(AmlScreeningService::class);
    $screeningService->performScreening($case, 'Homonyme Sanctioned Test', 'CD', 'individual');

    $alert = $case->alerts()->where('alert_type', AmlAlertType::SanctionsMatch)->first();
    expect($alert)->not->toBeNull();

    // Resolve as false positive
    $screeningService->resolveAlert(
        $alert,
        'false_positive',
        $admin,
        'Homonymie vérifiée avec la liste ONU. La date de naissance et le passeport sont différents.'
    );

    expect($alert->fresh()->status)->toBe(AmlAlertStatus::FalsePositive);
    // Case unblocked to manual review
    expect($case->fresh()->status)->toBe(ComplianceCaseStatus::ManualReview);
});

it('distinguishes between Source of Funds (SOF) and Source of Wealth (SOW) with encrypted accounts', function () {
    $user = User::factory()->investor()->create();
    $case = ComplianceCase::create([
        'case_number' => 'CASE-TEST-SOF',
        'user_id' => $user->id,
        'subject_type' => User::class,
        'subject_id' => $user->id,
    ]);

    $sof = SourceOfFundsDeclaration::create([
        'compliance_case_id' => $case->id,
        'user_id' => $user->id,
        'amount_minor' => 15000000, // $150,000 USD
        'currency' => 'USD',
        'funds_category' => SourceOfFundsCategory::BusinessDividends,
        'origin_description' => 'Dividendes perçus au titre de l\'exercice 2024 de la société Minière du Katanga.',
        'bank_name' => 'Rawbank RDC',
        'account_iban_or_number_encrypted' => 'CD5900012345678901234567890',
    ]);

    expect($sof->exists)->toBeTrue();
    expect($sof->amountFormatted())->toContain('150 000,00 USD');
    // Account number is encrypted at rest
    expect($sof->account_iban_or_number_encrypted)->toBe('CD5900012345678901234567890');

    $sow = SourceOfWealthDeclaration::create([
        'compliance_case_id' => $case->id,
        'user_id' => $user->id,
        'estimated_total_net_worth_minor' => 100000000,
        'currency' => 'USD',
        'wealth_description' => 'Fondateur de plusieurs entreprises dans le secteur logistique et minier depuis 15 ans.',
    ]);

    expect($sow->exists)->toBeTrue();
});

it('calculates multi-pillar AML risk scores using versioned RBA rules', function () {
    $user = User::factory()->investor()->create();
    $case = ComplianceCase::create([
        'case_number' => 'CASE-TEST-RBA',
        'user_id' => $user->id,
        'subject_type' => User::class,
        'subject_id' => $user->id,
        'status' => ComplianceCaseStatus::AutomatedChecks,
    ]);

    $riskService = app(AmlRiskAssessmentService::class);
    $assessment = $riskService->assessCase($case);

    expect($assessment->exists)->toBeTrue();
    expect($assessment->overall_score)->toBeGreaterThanOrEqual(0);
    expect($assessment->overall_score)->toBeLessThanOrEqual(100);
    expect($assessment->ruleVersion)->not->toBeNull();
    expect($case->fresh()->risk_level)->toBe($assessment->risk_level);
});

it('prepares confidential suspicious activity (DOS) dossiers without automatic transmission', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->investor()->create();
    $case = ComplianceCase::create([
        'case_number' => 'CASE-DOS-001',
        'user_id' => $user->id,
        'subject_type' => User::class,
        'subject_id' => $user->id,
    ]);

    $alert = AmlAlert::create([
        'compliance_case_id' => $case->id,
        'alert_type' => AmlAlertType::TransactionStructuring,
        'severity' => AmlRiskLevel::High,
        'status' => AmlAlertStatus::Open,
        'source' => 'monitoring',
        'title' => 'Suspicion de fractionnement de transactions',
        'description' => 'Plusieurs dépôts fractionnés sous le seuil de 10 000 USD constatés.',
    ]);

    $caseService = app(ComplianceCaseManagementService::class);

    $dosDossier = $caseService->prepareSuspiciousActivityDossier(
        $case,
        $admin,
        'Constat de 4 virements consécutifs de 9 500 USD sur une période de 48 heures sans justification économique apparente.',
        [$alert->id]
    );

    expect($dosDossier['dos_reference'])->toStartWith('DOS-CENAREF-PREP-');
    expect($dosDossier['confidentiality_rule'])->toContain('Anti-tipping off');
    expect($dosDossier['ready_for_authorized_declarant'])->toBeTrue();

    // Verify 10-year legal audit log creation
    expect(ComplianceAuditLog::where('event', 'dos.dossier_prepared')->exists())->toBeTrue();
});

it('enforces investment compliance gates for subscriptions, data rooms, and closings', function () {
    $gateService = app(InvestmentComplianceGateService::class);

    $unverifiedInvestor = User::factory()->investor()->create();
    // No KYC profile -> subscription blocked
    expect($gateService->canFinalizeSubscription($unverifiedInvestor))->toBeFalse();

    // Create approved KYC
    $kyc = KycProfile::create([
        'user_id' => $unverifiedInvestor->id,
        'valid_until' => now()->addYear(),
    ]);
    $kyc->forceFill(['status' => KycStatus::Approved])->save();

    expect($gateService->canFinalizeSubscription($unverifiedInvestor))->toBeTrue();

    // Check target company data room gate
    $entrepreneur = User::factory()->entrepreneur()->create();
    $project = Project::factory()->create(['user_id' => $entrepreneur->id]);

    // Founder unverified -> Data room blocked
    expect($gateService->canOpenDataRoom($project))->toBeFalse();

    // Approve founder KYC
    $founderKyc = KycProfile::create([
        'user_id' => $entrepreneur->id,
        'valid_until' => now()->addYear(),
    ]);
    $founderKyc->forceFill(['status' => KycStatus::Approved])->save();
    $project->unsetRelation('user');

    expect($gateService->canOpenDataRoom($project))->toBeTrue();
});

it('supports pre-incorporation projects without requiring RCCM and recommends OHADA form', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    $project = Project::factory()->create([
        'user_id' => $entrepreneur->id,
        'title' => 'AgriTech Kivu',
        'funding_target_minor' => 15000000, // $150,000 USD
    ]);

    $verificationService = app(ProjectVerificationService::class);
    $readinessService = app(CompanyFormationReadinessService::class);

    // Initialize at idea stage
    $verificationService->initializeProjectVerification($project, ProjectMaturityStage::Idea);

    expect($project->fresh()->maturity_stage)->toBe(ProjectMaturityStage::Idea);
    expect($project->fresh()->business_status)->toBe(BusinessStatus::NotIncorporated);

    // Update readiness & smart OHADA recommendation
    $readiness = $readinessService->updateReadiness($project, [
        'intended_company_name' => 'AgriTech Kivu SAS',
        'indicative_cofounders' => [
            ['name' => 'Founder 1', 'role' => 'CEO', 'projected_share_bps' => 6000],
            ['name' => 'Founder 2', 'role' => 'CTO', 'projected_share_bps' => 4000],
        ],
    ]);

    expect($readiness->recommended_legal_form)->toBe(LegalForm::Sas->value);
    expect($readiness->recommendation_rationale)->toContain('SAS');

    // Financial operations eligibility: allowed for preliminary matching, blocked for equity closing
    $eligibility = $verificationService->evaluateEligibility($project->fresh());
    expect($eligibility['allowed_actions'])->toContain('create_and_publish_project');
    expect($eligibility['allowed_actions'])->toContain('receive_non_binding_loi');
    expect($eligibility['blocked_actions'])->toContain('issue_shares_or_equity');
    expect($eligibility['blocked_actions'])->toContain('equity_closing');
});

it('transitions pre-incorporation project to incorporated company without erasing history', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    $project = Project::factory()->create(['user_id' => $entrepreneur->id, 'title' => 'Kin Solar']);

    $readinessService = app(CompanyFormationReadinessService::class);
    $kybService = app(BusinessVerificationService::class);

    $readinessService->updateReadiness($project, [
        'intended_company_name' => 'Kin Solar SARL',
        'intended_legal_form' => 'sarl',
    ]);

    // Transition to incorporated company
    $company = $readinessService->transitionToIncorporatedCompany(
        $project,
        $entrepreneur,
        [
            'company_name' => 'Kin Solar SARL',
            'rccm_number' => 'CD/KIN/RCCM/25-B-00888',
            'tax_identification_number' => 'A9998881K',
            'legal_form' => 'sarl',
        ],
        $kybService
    );

    expect($company->exists)->toBeTrue();
    expect($project->fresh()->business_status)->toBe(BusinessStatus::Incorporated);
    expect($company->businessVerifications()->count())->toBe(1);
});

it('verifies partner bank compliance matrix and independent clearance', function () {
    $bankService = app(PartnerBankComplianceService::class);
    $matrix = $bankService->getResponsibilitiesMatrix();

    expect($matrix)->toHaveKey('platform');
    expect($matrix)->toHaveKey('partner_bank');
    expect($matrix)->toHaveKey('investor');
    expect($matrix)->toHaveKey('target_company');

    expect($matrix['partner_bank']['responsibilities'])->toContain('Validation finale de l\'origine des fonds (Source of Funds).');
    expect($matrix['partner_bank']['responsibilities'])->toContain('Autorisation du dépôt des fonds et encaissement.');
});
