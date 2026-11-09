<?php

use App\Enums\FundingType;
use App\Models\FinancialInstrument;
use App\Models\FinancingChannel;
use App\Models\FinancingSource;
use App\Models\FinancingType;
use App\Models\Project;
use App\Models\Sector;
use App\Models\Stage;
use App\Models\User;
use App\Services\Financing\FinancingRequestService;
use App\Services\Matching\RuleMatchingService;
use Database\Seeders\FinancingCatalogSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SectorsSeeder;
use Database\Seeders\StagesSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
    $this->seed(SectorsSeeder::class);
    $this->seed(StagesSeeder::class);
    $this->seed(FinancingCatalogSeeder::class);
});

test('financing catalog seeders create all required types, instruments, sources and channels', function () {
    expect(FinancingType::count())->toBe(6);
    expect(FinancialInstrument::count())->toBeGreaterThanOrEqual(30);
    expect(FinancingSource::count())->toBe(13);
    expect(FinancingChannel::count())->toBe(7);

    // Verify Equity, Debt, Hybrid, Grant, Revenue-Share, Alternative
    $equityType = FinancingType::where('code', 'equity')->first();
    expect($equityType)->not->toBeNull();
    expect($equityType->instruments)->not->toBeEmpty();

    // Verify OHADA / RDC jurisdiction rules
    $safe = FinancialInstrument::where('code', 'safe')->first();
    expect($safe)->not->toBeNull();
    $safeRule = $safe->jurisdictionRules()->where('jurisdiction_code', 'CD')->first();
    expect($safeRule)->not->toBeNull();
    expect($safeRule->legal_status)->toBe('restricted');
    expect($safeRule->requires_incorporation)->toBeTrue();

    // Verify un-incorporated Idea stage instruments
    $bootstrapping = FinancialInstrument::where('code', 'bootstrapping')->first();
    $bootRule = $bootstrapping->jurisdictionRules()->where('jurisdiction_code', 'CD')->first();
    expect($bootRule->requires_incorporation)->toBeFalse();
});

test('entrepreneur can create a project with multiple instruments and blended financing components', function () {
    $user = User::factory()->entrepreneur()->create();
    $sector = Sector::where('is_active', true)->first();
    $stage = Stage::where('key', 'seed')->first();

    $commonShares = FinancialInstrument::where('code', 'common_shares')->first();
    $termLoan = FinancialInstrument::where('code', 'term_loan')->first();
    $grant = FinancialInstrument::where('code', 'investment_grant')->first();

    $payload = [
        'title' => 'Kongo AgriTech Platform',
        'description' => 'Plateforme technologique de transformation et distribution agricole en RDC.',
        'funding_target_minor' => 75_000_00, // $75,000 (within limits)
        'funding_min_minor' => 10_000_00,
        'funding_currency' => 'USD',
        'sectors' => [$sector->id],
        'stages' => [$stage->id],
        'financial_instruments' => [$commonShares->id, $termLoan->id, $grant->id],
        'financing_components' => [
            [
                'financial_instrument_id' => $commonShares->id,
                'target_amount_minor' => 40_000_00,
                'min_amount_minor' => 20_000_00,
                'is_mandatory' => true,
            ],
            [
                'financial_instrument_id' => $termLoan->id,
                'target_amount_minor' => 25_000_00,
                'min_amount_minor' => 10_000_00,
                'is_mandatory' => true,
            ],
            [
                'financial_instrument_id' => $grant->id,
                'target_amount_minor' => 10_000_00,
                'min_amount_minor' => 5_000_00,
                'is_mandatory' => false,
            ],
        ],
    ];

    $response = $this->actingAs($user)->post(route('entrepreneur.projects.store'), $payload);
    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $project = Project::where('title', 'Kongo AgriTech Platform')->first();
    expect($project)->not->toBeNull();

    // Verify investment_types was backward-compatibly derived
    expect($project->investment_types)->toContain('equity');
    expect($project->investment_types)->toContain('debt');
    expect($project->investment_types)->toContain('grant');

    // Verify structured financing request and components
    $request = $project->latestFinancingRequest;
    expect($request)->not->toBeNull();
    expect($request->total_amount_minor)->toBe(75_000_00);
    expect($request->components)->toHaveCount(3);

    $totalAllocated = $request->components->sum('target_amount_minor');
    expect($totalAllocated)->toBe(75_000_00);
});

test('rule matching service scores combined ticket and instrument compatibility accurately', function () {
    $rules = app(RuleMatchingService::class);

    $projectUser = User::factory()->entrepreneur()->create();
    $investorUser = User::factory()->investor()->create();

    $commonShares = FinancialInstrument::where('code', 'common_shares')->first();

    // Project asking for Equity
    $project = $projectUser->projects()->create([
        'title' => 'Mining Tech Solutions',
        'slug' => 'mining-tech-solutions',
        'description' => 'Innovations minières sécurisées',
        'funding_target_minor' => 50_000_00,
        'funding_min_minor' => 25_000_00,
        'funding_max_minor' => 50_000_00,
        'funding_currency' => 'USD',
        'investment_types' => ['equity'],
    ]);

    app(FinancingRequestService::class)->syncForProject($project, [
        'total_amount_minor' => 50_000_00,
        'currency' => 'USD',
        'components' => [
            ['financial_instrument_id' => $commonShares->id, 'target_amount_minor' => 50_000_00],
        ],
    ]);

    // Investor A: Equity investor with matching ticket
    $investorProfileEquity = $investorUser->investorProfile;
    $investorProfileEquity->update([
        'country_code' => 'CD',
        'ticket_currency' => 'USD',
        'ticket_min_minor' => 20_000_00,
        'ticket_max_minor' => 60_000_00,
        'funding_type' => FundingType::Equity,
    ]);

    $scoreEquity = $rules->scoreFunding($project, $investorProfileEquity);
    expect($scoreEquity)->toBeGreaterThanOrEqual(70);

    // Investor B: Debt investor with matching ticket range but incompatible instrument (Debt vs Equity)
    $investorUserB = User::factory()->investor()->create();
    $investorProfileDebt = $investorUserB->investorProfile;
    $investorProfileDebt->update([
        'country_code' => 'CD',
        'ticket_currency' => 'USD',
        'ticket_min_minor' => 20_000_00,
        'ticket_max_minor' => 60_000_00,
        'funding_type' => FundingType::Debt,
    ]);

    $scoreDebt = $rules->scoreFunding($project, $investorProfileDebt);
    // Debt investor must score significantly lower due to instrument mismatch
    expect($scoreDebt)->toBeLessThan($scoreEquity);
    expect($scoreDebt)->toBeLessThanOrEqual(45);
});
