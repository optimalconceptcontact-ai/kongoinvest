<?php

use App\Enums\CheckStatus;
use App\Enums\FinancialCheckType;
use App\Models\Company;
use App\Models\CompanyLegalRecord;
use App\Models\CompanyShareholding;
use App\Models\ContractFinancingComponent;
use App\Models\FinancialConsistencyCheck;
use App\Models\InvestmentContractWorkspace;
use App\Services\Contracts\Data\ConsistencyRunResult;
use App\Services\Contracts\FinancialConsistencyService;

/**
 * Contrôles financiers déterministes du prompt §10 sur le
 * scénario de référence §7 : SARLU + financement mixte. Aucune
 * donnée n'est inventée : une donnée insuffisante produit une
 * alerte « vérification humaine requise », jamais un arbitraire
 * silencieux.
 */
function buildMixedSarluConsistencyCase(array $workspaceOverrides = []): InvestmentContractWorkspace
{
    $company = Company::factory()->create();

    CompanyLegalRecord::factory()->verified()->create([
        'company_id' => $company->id,
        'share_count' => 300,
    ]);

    CompanyShareholding::factory()->founder()->verified()->create([
        'company_id' => $company->id,
        'holder_name' => 'Jean Mukendi',
        'share_count' => 300,
        'percentage_bps' => 10_000,
    ]);

    $workspace = InvestmentContractWorkspace::factory()
        ->mixedSarluReference()
        ->forCompany($company->id)
        ->create($workspaceOverrides);

    ContractFinancingComponent::factory()->create([
        'investment_contract_workspace_id' => $workspace->id,
        'type' => 'capital',
        'equity_mechanism' => 'capital_increase',
        'amount_minor' => 10_000_000, // 100 000 USD
    ]);

    ContractFinancingComponent::factory()->debt()->create([
        'investment_contract_workspace_id' => $workspace->id,
    ]);

    return $workspace->fresh();
}

function runFinancialChecks(InvestmentContractWorkspace $workspace): ConsistencyRunResult
{
    return app(FinancialConsistencyService::class)->run($workspace->fresh()->load('financingComponents'));
}

function checkOfType(InvestmentContractWorkspace $workspace, FinancialCheckType $type, string $batch): FinancialConsistencyCheck
{
    return $workspace->financialChecks()
        ->where('type', $type->value)
        ->where('batch', $batch)
        ->firstOrFail();
}

it('passes the coherent mixed scenario and flags only what is genuinely pending', function () {
    // Scénario §7 : tout est cohérent, seuls le taux d'intérêt,
    // les garanties et l'analyse de capacité restent à traiter —
    // précisément ce qu'indique le prompt (« les intérêts et
    // modalités définitives restant à négocier »).
    $workspace = buildMixedSarluConsistencyCase();
    $result = runFinancialChecks($workspace);

    expect($result->blocking)->toBeFalse();

    $status = fn (FinancialCheckType $type) => checkOfType($workspace, $type, $result->batch)->status->value;

    expect($status(FinancialCheckType::AmountConsistency))->toBe('passed');
    expect($status(FinancialCheckType::PercentageConsistency))->toBe('passed');
    // Post-money 400 k = pré 300 k + capital 100 k — la dette
    // n'affecte ni la valorisation ni la dilution.
    expect($status(FinancialCheckType::ValuationConsistency))->toBe('passed');
    expect($status(FinancialCheckType::DilutionCalculation))->toBe('passed');
    expect($status(FinancialCheckType::DoubleCounting))->toBe('passed');

    // Intérêts restant à négocier : alerte de clarification, pas
    // d'échec — et jamais une valeur inventée.
    expect($status(FinancialCheckType::InterestCalculation))->toBe('needs_review');
    expect($status(FinancialCheckType::CollateralCoverage))->toBe('needs_review');

    // Pas d'états financiers exploitables : prudence humaine.
    expect($status(FinancialCheckType::RepaymentCapacity))->toBe('needs_review');
    expect($status(FinancialCheckType::DscrRatio))->toBe('needs_review');
});

it('blocks finalisation when the debt is counted inside the post-money valuation', function () {
    $workspace = buildMixedSarluConsistencyCase(['post_money_minor' => 55_000_000]); // dette incluse → 550 k
    $result = runFinancialChecks($workspace);

    $valuation = checkOfType($workspace, FinancialCheckType::ValuationConsistency, $result->batch);

    expect($valuation->status->value)->toBe('failed');
    expect($valuation->severity->value)->toBe('critical');
    expect($valuation->severity->blocksClosing())->toBeTrue();
    expect($result->blocking)->toBeTrue();
    expect(app(FinancialConsistencyService::class)->hasOpenBlockingChecks($workspace->fresh()))->toBeTrue();
});

it('rejects percentages that do not total 100 %', function () {
    $workspace = buildMixedSarluConsistencyCase(['founder_share_bps' => 7000]); // 70 % + 25 % = 95 %
    $result = runFinancialChecks($workspace);

    $percentages = checkOfType($workspace, FinancialCheckType::PercentageConsistency, $result->batch);

    expect($percentages->status->value)->toBe('failed');
    expect($percentages->severity->value)->toBe('critical');
    expect($percentages->message)->toContain('ne totalise pas 100');
});

it('rejects a global amount that disagrees with the financing components', function () {
    $workspace = buildMixedSarluConsistencyCase(['total_investment_minor' => 24_000_000]); // 240 k contre 250 k
    $result = runFinancialChecks($workspace);

    $amounts = checkOfType($workspace, FinancialCheckType::AmountConsistency, $result->batch);

    expect($amounts->status->value)->toBe('failed');
    expect($amounts->severity->value)->toBe('critical');
});

it('flags an installment schedule whose total does not cover the principal', function () {
    $workspace = buildMixedSarluConsistencyCase();

    ContractFinancingComponent::factory()->debtWithTerms(1_200 /* 12 % */, [
        ['period' => 7, 'amount_minor' => 6_000_000],
        ['period' => 8, 'amount_minor' => 6_000_000],
    ])->create(['investment_contract_workspace_id' => $workspace->id]);
    // 12 M minor remboursés pour 15 M de principal : anomalie.

    $result = runFinancialChecks($workspace);

    $failed = collect($result->checks)
        ->where('type', FinancialCheckType::InterestCalculation)
        ->where('status', CheckStatus::Failed)
        ->first();

    expect($failed)->not->toBeNull();
    expect($failed['severity']->value)->toBe('high');
    expect($failed['message'])->toContain('principal');
});

it('warns when guarantees are mentioned but below the financed amount', function () {
    $workspace = buildMixedSarluConsistencyCase();

    ContractFinancingComponent::query()
        ->where('investment_contract_workspace_id', $workspace->id)
        ->where('type', 'debt')
        ->first()
        ->forceFill([
            'terms' => [
                'duration_months' => 48,
                'deferral_months' => 6,
                'guarantees' => [
                    ['label' => 'Nantissement du stock', 'value_minor' => 10_000_000], // 100 k < 150 k principal
                ],
            ],
        ])->save();

    $result = runFinancialChecks($workspace);

    $collateral = checkOfType($workspace, FinancialCheckType::CollateralCoverage, $result->batch);

    expect($collateral->status->value)->toBe('warning');
    expect($collateral->severity->value)->toBe('high');
    expect($collateral->message)->toContain('Couverture de 67');
});

it('accepts an explicitly negotiated unsecured loan', function () {
    $workspace = buildMixedSarluConsistencyCase();

    ContractFinancingComponent::query()
        ->where('investment_contract_workspace_id', $workspace->id)
        ->where('type', 'debt')
        ->first()
        ->forceFill([
            'terms' => ['duration_months' => 48, 'deferral_months' => 6, 'unsecured' => true],
        ])->save();

    $result = runFinancialChecks($workspace);

    expect(checkOfType($workspace, FinancialCheckType::CollateralCoverage, $result->batch)->status->value)->toBe('passed');
});

it('detects the same guarantee engaged in several debt components', function () {
    $workspace = buildMixedSarluConsistencyCase();

    $guaranteedTerms = [
        'duration_months' => 48,
        'deferral_months' => 6,
        'guarantees' => [
            ['label' => 'Nantissement du fonds de commerce', 'value_minor' => 20_000_000],
        ],
    ];

    ContractFinancingComponent::query()
        ->where('investment_contract_workspace_id', $workspace->id)
        ->where('type', 'debt')
        ->first()
        ->forceFill(['terms' => $guaranteedTerms])->save();

    // Une deuxième tranche engageant la MÊME sûreté.
    ContractFinancingComponent::factory()->create([
        'investment_contract_workspace_id' => $workspace->id,
        'type' => 'debt',
        'equity_mechanism' => null,
        'is_primary' => false,
        'sort_order' => 3,
        'amount_minor' => 5_000_000,
        'terms' => $guaranteedTerms,
    ]);

    $result = runFinancialChecks($workspace);

    $doubleCounting = collect($result->checks)
        ->where('type', FinancialCheckType::DoubleCounting)
        ->where('status', CheckStatus::Failed)
        ->first();

    expect($doubleCounting)->not->toBeNull();
    expect($doubleCounting['message'])->toContain('fonds de commerce');
});

it('keeps an auditable history of every run instead of overwriting past checks', function () {
    $workspace = buildMixedSarluConsistencyCase();
    $first = runFinancialChecks($workspace);

    // Une correction commerciale enregistrée → nouvelle passe ;
    // les anciens contrôles RESTENT en historique.
    $workspace->forceFill(['post_money_minor' => 55_000_000])->save();

    $second = runFinancialChecks($workspace);

    expect($second->batch)->not->toBe($first->batch);
    expect($workspace->financialChecks()->distinct()->count('batch'))->toBe(2);
    expect(FinancialConsistencyCheck::query()->where('batch', $first->batch)->count())->toBeGreaterThan(0);
});

it('clears the blocking state once a human resolves the critical findings', function () {
    $workspace = buildMixedSarluConsistencyCase(['post_money_minor' => 55_000_000]); // dette incluse → 550 k

    runFinancialChecks($workspace);

    expect(app(FinancialConsistencyService::class)->hasOpenBlockingChecks($workspace->fresh()))->toBeTrue();

    // Un professionnel lève l'anomalie : résolution traçable
    // (qui + quand), distincte du flux commercial.
    $workspace->financialChecks()
        ->where('severity', 'critical')
        ->whereNull('resolved_at')
        ->update(['resolved_at' => now(), 'resolved_by_user_id' => $workspace->dealRoom->entrepreneur_user_id]);

    expect(app(FinancialConsistencyService::class)->hasOpenBlockingChecks($workspace->fresh()))->toBeFalse();
});
