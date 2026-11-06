<?php

use App\Enums\LegalForm;
use App\Models\Company;
use App\Models\CompanyLegalRecord;
use App\Models\CompanyShareholding;
use App\Models\ContractFinancingComponent;
use App\Models\InvestmentContractWorkspace;
use App\Services\Contracts\CapTableCalculationService;

/**
 * Scénario de référence (prompt §7) : SARLU, financement mixte,
 * fondateur flexible. Pré-money 300 000 USD, post-money 400 000
 * USD ; 100 000 USD en capital, 150 000 USD en dette (48 mois,
 * différé 6 mois, intérêts restant à négocier) ; participation
 * visée 25 % investisseur / 75 % fondateur.
 */
function sarluCompanyForCapitalScenario(int $shareCount = 300, bool $verified = true): Company
{
    $company = Company::factory()->create();

    CompanyLegalRecord::factory()->create([
        'company_id' => $company->id,
        'legal_form' => LegalForm::Sarl->value,
        'capital_minor' => 30_000_000,
        'capital_currency' => 'USD',
        'share_count' => $shareCount,
        'nominal_value_minor' => $shareCount > 0 ? intdiv(30_000_000, $shareCount) : null,
        'verification' => $verified ? 'verified' : 'unverified',
    ]);

    $holding = CompanyShareholding::factory()->founder();

    if ($verified) {
        $holding = $holding->verified();
    }

    $holding->create([
        'company_id' => $company->id,
        'holder_name' => 'Jean Mukendi',
        'share_count' => $shareCount,
        'percentage_bps' => 10_000,
    ]);

    return $company;
}

function mixedScenarioWorkspace(Company $company, array $overrides = []): InvestmentContractWorkspace
{
    $workspace = InvestmentContractWorkspace::factory()
        ->mixedSarluReference()
        ->forCompany($company->id)
        ->create($overrides);

    ContractFinancingComponent::factory()->create([
        'investment_contract_workspace_id' => $workspace->id,
        'type' => 'capital',
        'equity_mechanism' => 'capital_increase',
        'is_primary' => true,
        'amount_minor' => 10_000_000, // 100 000 USD
    ]);

    ContractFinancingComponent::factory()->debt()->create([
        'investment_contract_workspace_id' => $workspace->id,
    ]);

    return $workspace;
}

it('computes the reference mixed SARLU scenario exactly: 100 k capital + 150 k debt → 25 % / 75 %', function () {
    $company = sarluCompanyForCapitalScenario(shareCount: 300);
    $workspace = mixedScenarioWorkspace($company);

    $capTable = app(CapTableCalculationService::class)->calculate($workspace);

    expect($capTable)->not->toBeNull();
    expect($capTable->mechanism)->toBe('capital_increase');

    // 300 000 base × 100 000 / 300 000 = 100 parts nouvelles.
    expect($capTable->newShares)->toBe(100);
    expect($capTable->shareCountBefore)->toBe(300);
    expect($capTable->shareCountAfter)->toBe(400);
    expect($capTable->pricePerShareMinor)->toBe(100_000); // 1 000 USD / part
    expect($capTable->pricePerShareExact)->toBeTrue();

    // Participation exacte : 100 / 400 = 25 %, fondateur 75 %.
    expect($capTable->investorShareBps)->toBe(2_500);
    expect($capTable->founderShareBps)->toBe(7_500);
    expect($capTable->targetInvestorShareBps)->toBe(2_500);
    expect($capTable->targetAchievable)->toBeTrue();

    // La dette de 150 000 USD ne fait JAMAIS partie des fonds
    // propres : ni dans la valorisation, ni dans la dilution
    // (prompt §7, « ne pas les inclure dans le calcul de dilution »).
    expect($capTable->capitalAmountMinor)->toBe(10_000_000); // 100 k, pas 250 k
    expect($capTable->postMoneyMinor)->toBe(40_000_000); // 400 k, pas 550 k
    expect($capTable->warnings)->toBeEmpty();

    // Entrées : le fondateur dilué + l'investisseur entrant.
    expect(count($capTable->entries))->toBe(2);
    $founderEntry = collect($capTable->entries)->firstWhere('is_founder', true);
    $investorEntry = collect($capTable->entries)->firstWhere('is_investor', true);
    expect($founderEntry['shares_after'])->toBe(300);
    expect($founderEntry['percentage_after_bps'])->toBe(7_500);
    expect($investorEntry['shares_after'])->toBe(100);
    expect($investorEntry['percentage_after_bps'])->toBe(2_500);

    // Snapshot persistable dans post_operation_cap_table.
    $snapshot = $capTable->toSnapshot();
    expect($snapshot['mechanism'])->toBe('capital_increase');
    expect($snapshot['investor_share_bps'])->toBe(2_500);
    expect($snapshot['capital_amount_minor'])->toBe(10_000_000);
});

it('signals indivisible parts instead of silently rounding to the negotiated share', function () {
    // 100 parts : 100 k capital sur 300 k pré-money = 33,33 parts à
    // émettre — arrondi 33 → 24,81 % ≠ 25 % négocié. Le système
    // signale l'incertitude au lieu d'inventer (prompt §16, r.12).
    $company = sarluCompanyForCapitalScenario(shareCount: 100);
    $workspace = mixedScenarioWorkspace($company);

    $capTable = app(CapTableCalculationService::class)->calculate($workspace);

    expect($capTable->newShares)->toBe(33);
    expect($capTable->investorShareBps)->toBe(2_481); // 33 parts sur 133 après création des nouvelles
    expect($capTable->targetAchievable)->toBeFalse();

    expect($capTable->warnings)->toHaveCount(1);
    expect(implode(' ', $capTable->warnings))->toContain('nombre entier');
});

it('never lets debt or an unvalidated convertible enter the cap table', function () {
    $company = sarluCompanyForCapitalScenario();
    $workspace = mixedScenarioWorkspace($company);

    // Un instrument étranger (prêt convertible type SAFE) ne se
    // transpose pas automatiquement en droit OHADA : il ne dilue
    // PAS tant que sa conversion n'est pas validée.
    ContractFinancingComponent::factory()->create([
        'investment_contract_workspace_id' => $workspace->id,
        'type' => 'convertible',
        'equity_mechanism' => null,
        'is_primary' => false,
        'sort_order' => 3,
        'amount_minor' => 5_000_000,
        'terms' => ['converts_to_equity' => true],
    ]);

    $capTable = app(CapTableCalculationService::class)->calculate($workspace);

    expect($capTable->capitalAmountMinor)->toBe(10_000_000); // convertible exclu
    expect($capTable->shareCountAfter)->toBe(400);
    expect($capTable->warnings)->toContain(
        'Composante convertible présente : sa dilution future n’est pas comptée ici — le mécanisme de conversion doit faire l’objet d’une validation juridique spécifique (prompt §16, règle 11).'
    );
});

it('warns on unverified legal data instead of trusting it', function () {
    $company = sarluCompanyForCapitalScenario(verified: false);
    $workspace = mixedScenarioWorkspace($company);

    $capTable = app(CapTableCalculationService::class)->calculate($workspace);

    expect($capTable->verifiedData)->toBeFalse();
    expect(implode(' ', $capTable->warnings))->toContain('non entièrement vérifiées');
});

it('returns null when the capital structure is not established yet', function () {
    // Sans operator_id reliée : pas de structure = pas de calcul
    // inventé.
    $workspace = InvestmentContractWorkspace::factory()->mixedSarluReference()->create();

    expect(app(CapTableCalculationService::class)->calculate($workspace))->toBeNull();
});

it('computes title transfer without issuing any new shares', function () {
    // Cession : l'investisseur rachète 25 % des titres du
    // fondateur — capital inchangé, pas d'augmentation.
    $company = sarluCompanyForCapitalScenario(shareCount: 400);
    $workspace = mixedScenarioWorkspace($company, [
        'pre_money_minor' => 40_000_000,
        'post_money_minor' => 40_000_000,
    ]);

    $capitalComponent = ContractFinancingComponent::query()
        ->where('investment_contract_workspace_id', $workspace->id)
        ->where('type', 'capital')
        ->first();
    $capitalComponent->forceFill(['equity_mechanism' => 'title_transfer'])->save();

    $capTable = app(CapTableCalculationService::class)->calculate($workspace);

    expect($capTable->mechanism)->toBe('title_transfer');
    expect($capTable->newShares)->toBe(0);
    expect($capTable->shareCountAfter)->toBe(400); // capital inchangé
    expect($capTable->investorShareBps)->toBe(2_500);

    $founderEntry = collect($capTable->entries)->firstWhere('is_founder', true);
    $investorEntry = collect($capTable->entries)->firstWhere('is_investor', true);
    expect($founderEntry['shares_after'])->toBe(300); // 400 − 100 cédées
    expect($investorEntry['shares_after'])->toBe(100);
    expect($capTable->targetAchievable)->toBeTrue();
});

it('infers the transferred share from the paid price when no explicit target exists', function () {
    $company = sarluCompanyForCapitalScenario(shareCount: 400);
    $workspace = mixedScenarioWorkspace($company, [
        'pre_money_minor' => 40_000_000,
        'post_money_minor' => 40_000_000,
        'investor_share_bps' => null,
        'founder_share_bps' => null,
    ]);

    $capitalComponent = ContractFinancingComponent::query()
        ->where('investment_contract_workspace_id', $workspace->id)
        ->where('type', 'capital')
        ->first();
    $capitalComponent->forceFill(['equity_mechanism' => 'title_transfer'])->save();

    $capTable = app(CapTableCalculationService::class)->calculate($workspace);

    // 100 000 payés / 400 000 de valeur = 25 % → 100 parts sur 400.
    expect($capTable->targetInvestorShareBps)->toBe(2_500);
    expect($capTable->investorShareBps)->toBe(2_500);
});
