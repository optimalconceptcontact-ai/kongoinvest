<?php

use App\Actions\Contracts\CreateInvestmentContractWorkspace;
use App\Actions\Contracts\UpdateOperationStructure;
use App\Enums\DataVerificationStatus;
use App\Enums\EquityMechanism;
use App\Enums\FinancingCategory;
use App\Enums\FinancingComponentType;
use App\Enums\LegalForm;
use App\Enums\WorkspaceStatus;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\CompanyLegalRecord;
use App\Models\CompanyShareholding;
use App\Models\ContractFinancingComponent;
use App\Models\DealRoom;
use App\Models\FinancialConsistencyCheck;
use App\Models\InvestmentContractWorkspace;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Contracts\FinancialConsistencyService;
use App\Services\SubscriptionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

// Helpers nommés de façon unique pour cohabiter avec les autres
// fichiers de tests dans un même run de la suite.

function cstrSubscribe(User $user, string $planKey): void
{
    app(SubscriptionService::class)->switchPlan(
        $user,
        SubscriptionPlan::query()->where('key', $planKey)->firstOrFail(),
    );
}

/**
 * Opération de référence : SARLU 1 000 parts, associé fondateur
 * unique, données vérifiées — la structure du capital (prompt §7)
 * est calculable de bout en bout.
 */
function cstrWorkspace(): InvestmentContractWorkspace
{
    $entrepreneur = User::factory()->entrepreneur()->create();
    $investor = User::factory()->investor()->create();
    $room = DealRoom::factory()->create([
        'entrepreneur_user_id' => $entrepreneur->id,
        'investor_user_id' => $investor->id,
    ]);
    cstrSubscribe($entrepreneur, 'entrepreneur');

    $company = Company::factory()->create();
    CompanyLegalRecord::factory()->create([
        'company_id' => $company->id,
        'legal_form' => LegalForm::Sarlu->value,
        'share_count' => 1000,
        'capital_minor' => 10_000_00,
        'capital_currency' => 'USD',
        'verification' => DataVerificationStatus::Verified->value,
        'is_current' => true,
    ]);
    CompanyShareholding::factory()
        ->founder()
        ->create([
            'company_id' => $company->id,
            'holder_user_id' => $entrepreneur->id,
            'holder_name' => $entrepreneur->name,
            'share_count' => 1000,
        ]);

    return app(CreateInvestmentContractWorkspace::class)
        ->handle($entrepreneur, $room, ['company_id' => $company->id]);
}

function cstrRunUpdate(User $user, InvestmentContractWorkspace $workspace, array $input): ?string
{
    try {
        app(UpdateOperationStructure::class)->handle($user, $workspace, $input);

        return null;
    } catch (ValidationException $exception) {
        return collect($exception->errors())->flatten()->implode(' ');
    }
}

it('records the operation structure, derives the mixed category and projects the cap table', function () {
    $workspace = cstrWorkspace();

    $workspace = app(UpdateOperationStructure::class)->handle(
        $workspace->dealRoom->entrepreneur,
        $workspace,
        [
            'pre_money_minor' => 4_000_000_00,
            'total_investment_minor' => 1_000_000_00,
            'founder_share_bps' => 8000,
            'investor_share_bps' => 2000,
            'components' => [
                [
                    'type' => FinancingComponentType::Capital->value,
                    'equity_mechanism' => EquityMechanism::CapitalIncrease->value,
                    'amount_minor' => 700_000_00,
                    'is_primary' => true,
                ],
                [
                    'type' => FinancingComponentType::Debt->value,
                    'amount_minor' => 300_000_00,
                    'terms' => [
                        'rate_bps' => 1200,
                        'duration_months' => 36,
                        'unsecured' => true,
                    ],
                ],
            ],
        ],
    );

    // Composantes distinctes, ordre conservé, dette sans mécanisme.
    $components = $workspace->financingComponents()->orderBy('sort_order')->get();
    expect($components)->toHaveCount(2)
        ->and($components[0]->type)->toBe(FinancingComponentType::Capital)
        ->and($components[0]->equity_mechanism)->toBe(EquityMechanism::CapitalIncrease)
        ->and($components[1]->type)->toBe(FinancingComponentType::Debt)
        ->and($components[1]->equity_mechanism)->toBeNull()
        ->and($components[1]->terms['rate_bps'])->toBe(1200);

    // Catégorie déduite, jamais saisie ; la dette N'ENTRE pas dans
    // la valorisation ni la dilution (§16, r.5) : post-money
    // implicite = pré-money + capital uniquement.
    expect($workspace->financing_category)->toBe(FinancingCategory::Mixed)
        ->and($workspace->post_money_minor)->toBe(4_000_000_00 + 700_000_00)
        ->and($workspace->status)->toBe(WorkspaceStatus::Setup)
        ->and($workspace->post_operation_cap_table['new_shares'])->toBe(175)
        ->and($workspace->post_operation_cap_table['mechanism'])->toBe(EquityMechanism::CapitalIncrease->value);

    // L'audit trace la mise à jour de structure.
    expect(AuditLog::query()->where('action', 'workspace.structure_updated')->count())->toBe(1);
});

it('refuses a legal entry mechanism on a debt component and more than one primary instrument', function () {
    $workspace = cstrWorkspace();
    $user = $workspace->dealRoom->entrepreneur;

    $mechanismError = cstrRunUpdate($user, $workspace, [
        'components' => [
            [
                'type' => FinancingComponentType::Debt->value,
                'equity_mechanism' => EquityMechanism::CapitalIncrease->value,
                'amount_minor' => 100_000_00,
            ],
        ],
    ]);

    expect($mechanismError)->toContain('ne s\'applique qu\'à une composante en capital')
        ->and(ContractFinancingComponent::query()->count())->toBe(0);

    $primaryError = cstrRunUpdate($user, $workspace, [
        'components' => [
            ['type' => FinancingComponentType::Debt->value, 'amount_minor' => 1, 'is_primary' => true],
            ['type' => FinancingComponentType::Capital->value, 'amount_minor' => 2, 'is_primary' => true],
        ],
    ]);

    expect($primaryError)->toContain('Une seule composante peut porter le rôle d\'instrument principal')
        ->and(ContractFinancingComponent::query()->count())->toBe(0);
});

it('refuses unknown terms keys and an inconsistent deferral versus the duration', function () {
    $workspace = cstrWorkspace();
    $user = $workspace->dealRoom->entrepreneur;

    expect(cstrRunUpdate($user, $workspace, [
        'components' => [
            ['type' => FinancingComponentType::Debt->value, 'amount_minor' => 1, 'terms' => ['grace' => 3]],
        ],
    ]))->toContain('Modalités de composante non prévues');

    expect(cstrRunUpdate($user, $workspace, [
        'components' => [
            ['type' => FinancingComponentType::Debt->value, 'amount_minor' => 1,
                'terms' => ['duration_months' => 12, 'deferral_months' => 12]],
        ],
    ]))->toContain('inférieur');

    expect(ContractFinancingComponent::query()->count())->toBe(0);
});

it('extends the structure change exclusively to participants on open operations', function () {
    $workspace = cstrWorkspace();

    $outsider = User::factory()->create();
    expect(cstrRunUpdate($outsider, $workspace, ['pre_money_minor' => 100]))->toContain('participants');

    $workspace->forceFill(['status' => WorkspaceStatus::Closed->value, 'closed_at' => now()])->save();
    expect(cstrRunUpdate($workspace->dealRoom->investor, $workspace, ['pre_money_minor' => 100]))
        ->toContain('clôturée ou annulée');
});

it('runs financial checks that flag debt counted inside valuations', function () {
    $workspace = cstrWorkspace();

    app(UpdateOperationStructure::class)->handle($workspace->dealRoom->entrepreneur, $workspace, [
        // 600k capital + 400k dette ; la post-money intègre la
        // dette : contradiction critique attendue (§16, r.5).
        'pre_money_minor' => 4_000_000_00,
        'post_money_minor' => 4_000_000_00 + 1_000_000_00,
        'total_investment_minor' => 1_000_000_00,
        'founder_share_bps' => 8000,
        'investor_share_bps' => 2000,
        'components' => [
            ['type' => FinancingComponentType::Capital->value, 'amount_minor' => 600_000_00, 'is_primary' => true],
            ['type' => FinancingComponentType::Debt->value, 'amount_minor' => 400_000_00],
        ],
    ]);

    $result = app(FinancialConsistencyService::class)->run($workspace->fresh());

    expect($result->blocking)->toBeTrue();

    $critical = FinancialConsistencyCheck::query()
        ->where('investment_contract_workspace_id', $workspace->id)
        ->where('severity', 'critical')
        ->where('status', 'failed')
        ->get();

    // Au moins les contrôles de valorisation / double comptage
    // signalent la dette traitée en fonds propres — jamais une
    // dette dilutive en silence.
    expect($critical->pluck('type')->map(fn ($type) => $type->value)->values()->all())
        ->each->toBeIn(['valuation_consistency', 'double_counting']);
});
