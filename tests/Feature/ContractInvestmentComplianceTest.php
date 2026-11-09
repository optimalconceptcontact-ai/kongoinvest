<?php

use App\Actions\Contracts\CloseOperation;
use App\Actions\Contracts\CreateInvestmentContractWorkspace;
use App\Actions\Contracts\RespondToContract;
use App\Actions\Escrow\ReleaseEscrow;
use App\Actions\Payments\CreatePayment;
use App\Enums\AmlAlertStatus;
use App\Enums\AmlAlertType;
use App\Enums\AmlRiskLevel;
use App\Enums\ComplianceCaseStatus;
use App\Enums\KycStatus;
use App\Enums\LegalForm;
use App\Enums\PaymentProviderName;
use App\Enums\PaymentPurpose;
use App\Enums\SourceOfFundsCategory;
use App\Models\AmlAlert;
use App\Models\BeneficialOwner;
use App\Models\BusinessVerification;
use App\Models\Company;
use App\Models\ComplianceCase;
use App\Models\Contract;
use App\Models\DealRoom;
use App\Models\Escrow;
use App\Models\InvestmentContractWorkspace;
use App\Models\KycProfile;
use App\Models\Payment;
use App\Models\SourceOfFundsDeclaration;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Compliance\InvestmentComplianceGateService;
use App\Services\SubscriptionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

function cicSubscribe(User $user, string $planKey = 'entrepreneur'): void
{
    app(SubscriptionService::class)->switchPlan(
        $user,
        SubscriptionPlan::query()->where('key', $planKey)->firstOrFail(),
    );
}

function cicApprovedKyc(User $user): KycProfile
{
    $profile = KycProfile::create(['user_id' => $user->id]);
    $profile->forceFill([
        'status' => KycStatus::Approved,
        'valid_until' => now()->addYear(),
    ])->save();

    return $profile;
}

function cicBlockedCase(User $user, string $number): ComplianceCase
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

function cicSanctionsAlert(ComplianceCase $case, AmlAlertStatus $status = AmlAlertStatus::Open): AmlAlert
{
    return AmlAlert::create([
        'compliance_case_id' => $case->id,
        'alert_type' => AmlAlertType::SanctionsMatch,
        'severity' => AmlRiskLevel::Critical,
        'status' => $status,
        'source' => 'test',
        'title' => 'Correspondance sanctions',
        'description' => 'Correspondance de nom à vérifier humainement.',
    ]);
}

function cicRun(callable $callback): ?string
{
    try {
        $callback();

        return null;
    } catch (ValidationException $exception) {
        return collect($exception->errors())->flatten()->implode(' ');
    }
}

it('freezes opening a contract workspace when a participant case is blocked', function () {
    $entrepreneur = User::factory()->entrepreneur()->create();
    $investor = User::factory()->investor()->create();

    $room = DealRoom::factory()->create([
        'entrepreneur_user_id' => $entrepreneur->id,
        'investor_user_id' => $investor->id,
    ]);

    cicSubscribe($entrepreneur);
    cicBlockedCase($investor, 'CASE-CIC-WORKSPACE');

    $message = cicRun(fn () => app(CreateInvestmentContractWorkspace::class)->handle($entrepreneur, $room));

    expect($message)->toContain('gelée')
        ->and(InvestmentContractWorkspace::query()->where('deal_room_id', $room->id)->exists())->toBeFalse();
});

it('freezes legacy contract acceptance when a party has an open sanctions alert', function () {
    $contract = Contract::factory()->create();

    $case = ComplianceCase::create([
        'case_number' => 'CASE-CIC-ACCEPT',
        'user_id' => $contract->dealRoom->investor_user_id,
        'subject_type' => User::class,
        'subject_id' => $contract->dealRoom->investor_user_id,
    ]);

    cicSanctionsAlert($case);

    $message = cicRun(fn () => app(RespondToContract::class)->accept($contract->dealRoom->entrepreneur, $contract));

    expect($message)->toContain('alerte sanctions')
        ->and($contract->fresh()->entrepreneur_accepted_at)->toBeNull();
});

it('freezes escrow release on an open sanctions alert and still releases clean legacy escrows', function () {
    $escrow = Escrow::factory()->held()->create();

    // Clean legacy parties keep working.
    app(ReleaseEscrow::class)->handle($escrow->investor, $escrow);
    expect($escrow->fresh()->status->value)->toBe('released');

    $blockedEscrow = Escrow::factory()->held()->create();
    $case = ComplianceCase::create([
        'case_number' => 'CASE-CIC-RELEASE',
        'user_id' => $blockedEscrow->entrepreneur_user_id,
        'subject_type' => User::class,
        'subject_id' => $blockedEscrow->entrepreneur_user_id,
    ]);

    cicSanctionsAlert($case);

    $message = cicRun(fn () => app(ReleaseEscrow::class)->handle($blockedEscrow->investor, $blockedEscrow));

    expect($message)->toContain('alerte sanctions')
        ->and($blockedEscrow->fresh()->status->value)->toBe('held');
});

it('requires a verified identity at an explicitly enabled checkpoint', function () {
    config()->set('compliance.gates.identity_required_stages', [
        InvestmentComplianceGateService::STAGE_WORKSPACE_OPENING,
    ]);

    $entrepreneur = User::factory()->entrepreneur()->create();
    $investor = User::factory()->investor()->create();

    $room = DealRoom::factory()->create([
        'entrepreneur_user_id' => $entrepreneur->id,
        'investor_user_id' => $investor->id,
    ]);

    cicSubscribe($entrepreneur);

    $message = cicRun(fn () => app(CreateInvestmentContractWorkspace::class)->handle($entrepreneur, $room));

    expect($message)->toContain('identité');

    cicApprovedKyc($entrepreneur);
    cicApprovedKyc($investor);

    $workspace = app(CreateInvestmentContractWorkspace::class)->handle($entrepreneur, $room);

    expect($workspace->exists)->toBeTrue();
});

it('blocks signatures once a compliance file is started but not approved', function () {
    $workspace = InvestmentContractWorkspace::factory()->create();
    $investor = $workspace->investor();

    $profile = KycProfile::create(['user_id' => $investor->id]);
    $profile->forceFill([
        'status' => KycStatus::Submitted,
        'submitted_at' => now(),
    ])->save();

    $result = app(InvestmentComplianceGateService::class)->evaluateWorkspace(
        $workspace,
        InvestmentComplianceGateService::STAGE_SIGNATURE,
    );

    expect($result->allowed())->toBeFalse()
        ->and($result->codes())->toContain('identity_not_verified');
});

it('blocks closing until KYB and qualified UBO files are verified when enabled', function () {
    config()->set('compliance.gates.kyb_required_stages', [
        InvestmentComplianceGateService::STAGE_CLOSING,
    ]);
    config()->set('compliance.gates.ubo_required_stages', [
        InvestmentComplianceGateService::STAGE_CLOSING,
    ]);

    $entrepreneur = User::factory()->entrepreneur()->create();
    $investor = User::factory()->investor()->create();

    $room = DealRoom::factory()->create([
        'entrepreneur_user_id' => $entrepreneur->id,
        'investor_user_id' => $investor->id,
    ]);

    $company = Company::factory()->create();

    $workspace = InvestmentContractWorkspace::factory()
        ->forCompany($company->id)
        ->create([
            'deal_room_id' => $room->id,
            'status' => 'negotiation',
        ]);

    $workspace->forceFill(['legal_reviewed_at' => now()])->save();

    // 1. Aucun dossier KYB.
    expect(cicRun(fn () => app(CloseOperation::class)->handle($entrepreneur, $workspace)))
        ->toContain('KYB');

    // 2. KYB validé, mais aucun bénéficiaire effectif vérifié.
    $verification = BusinessVerification::create([
        'company_id' => $company->id,
        'company_name' => $company->name,
        'legal_form' => LegalForm::Sarl,
    ]);
    $verification->status = 'verified';
    $verification->verified_at = now();
    $verification->save();

    expect(cicRun(fn () => app(CloseOperation::class)->handle($entrepreneur, $workspace->fresh())))
        ->toContain('bénéficiaire effectif');

    // 3. UBO qualifié vérifié : la clôture passe.
    $ubo = BeneficialOwner::create([
        'business_verification_id' => $verification->id,
        'first_name' => 'Jean',
        'last_name' => 'Mukendi',
        'nationality' => 'CD',
        'country_of_residence' => 'CD',
        'direct_ownership_bps' => 6000,
        'control_nature' => 'direct_equity',
        'meets_statutory_threshold' => true,
    ]);
    $ubo->forceFill(['is_verified' => true])->save();

    $closed = app(CloseOperation::class)->handle($entrepreneur, $workspace->fresh());

    expect($closed->isClosed())->toBeTrue();
});

it('requires a source-of-funds declaration for an onboarded investor above the threshold', function () {
    $investor = User::factory()->investor()->create();

    cicApprovedKyc($investor);

    $service = app(InvestmentComplianceGateService::class);

    $missing = $service->evaluateUser(
        $investor,
        InvestmentComplianceGateService::STAGE_ESCROW_FUNDING,
        50_000_00,
        'USD',
    );

    expect($missing->allowed())->toBeFalse()
        ->and($missing->codes())->toContain('source_of_funds_missing');

    SourceOfFundsDeclaration::create([
        'user_id' => $investor->id,
        'amount_minor' => 50_000_00,
        'currency' => 'USD',
        'funds_category' => SourceOfFundsCategory::BusinessDividends,
        'origin_description' => 'Dividendes 2024 de la société minière.',
        'bank_name' => 'Rawbank RDC',
        'bank_country' => 'CD',
    ]);

    $declared = $service->evaluateUser(
        $investor,
        InvestmentComplianceGateService::STAGE_ESCROW_FUNDING,
        50_000_00,
        'USD',
    );

    // A recorded but unverified declaration is a warning, not an
    // automatic block: the compliance team still has to verify it.
    expect($declared->allowed())->toBeTrue()
        ->and($declared->codes())->toContain('source_of_funds_unverified');
});

it('freezes escrow funding before any payment intent when the investor is blocked', function () {
    $escrow = Escrow::factory()->create();
    cicBlockedCase($escrow->investor, 'CASE-CIC-FUNDING');

    expect(fn () => app(CreatePayment::class)->handle(
        payer: $escrow->investor,
        purpose: PaymentPurpose::Investment,
        provider: PaymentProviderName::Stripe,
        amountMinor: 100_00,
        currency: 'USD',
        payable: $escrow,
    ))->toThrow(ValidationException::class);

    expect(Payment::query()->count())->toBe(0);
});

it('exposes stable codes and subjects in the structured gate result', function () {
    $investor = User::factory()->investor()->create();
    cicBlockedCase($investor, 'CASE-CIC-STRUCTURED');

    $result = app(InvestmentComplianceGateService::class)->evaluateUser(
        $investor,
        InvestmentComplianceGateService::STAGE_SUBSCRIPTION,
    );

    expect($result->allowed())->toBeFalse()
        ->and($result->blockingIssues())->toHaveCount(1)
        ->and($result->toArray()['issues'][0]['code'])->toBe('compliance_case_blocked')
        ->and($result->toArray()['issues'][0]['subject'])->toBe($investor->name);
});
