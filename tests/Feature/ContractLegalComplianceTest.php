<?php

use App\Actions\Contracts\CloseOperation;
use App\Data\ContractWorkspaceData;
use App\Enums\CheckStatus;
use App\Enums\ContractDocumentType;
use App\Enums\LegalCheckType;
use App\Enums\LegalForm;
use App\Models\Company;
use App\Models\CompanyLegalRecord;
use App\Models\CompanyShareholding;
use App\Models\ContractDocument;
use App\Models\ContractFinancingComponent;
use App\Models\FinancialConsistencyCheck;
use App\Models\InvestmentContractWorkspace;
use App\Models\LegalComplianceCheck;
use App\Services\Contracts\Data\LegalComplianceRunResult;
use App\Services\Contracts\LegalComplianceService;
use Illuminate\Validation\ValidationException;

/**
 * Contrôles juridiques déterministes du prompt §10 : même
 * mécanique batch harness que les contrôles financiers
 * (fin-… / leg-…). Aucune donnée juridique n'est inventée : une
 * donnée manquante produit NeedsReview, une contradiction de
 * matière (décision sociale, acte constitutif de titres)
 * produit un échec critique qui fige la finalisation.
 */

// Helpers préfixés « clegal » pour cohabiter avec les autres
// fichiers de la suite dans un même run.

function clegalWorkspace(?Company $company = null, array $overrides = []): InvestmentContractWorkspace
{
    $workspace = InvestmentContractWorkspace::factory()->create($overrides);

    if ($company !== null) {
        $workspace->forceFill(['company_id' => $company->id])->save();
    }

    return $workspace->fresh();
}

/**
 * Entreprise + fiche juridique courante + structure de détention
 * enregistrée : toutes les sources probantes du moteur.
 */
function clegalCompanyWithRecord(array $recordAttributes = [], array $holdings = []): array
{
    $company = Company::factory()->create();

    CompanyLegalRecord::factory()->verified()->create($recordAttributes + ['company_id' => $company->id]);

    foreach ($holdings as $holding) {
        CompanyShareholding::factory()->verified()->create($holding + ['company_id' => $company->id]);
    }

    return [$company, $company->fresh()];
}

function clegalRunChecks(InvestmentContractWorkspace $workspace): LegalComplianceRunResult
{
    return app(LegalComplianceService::class)->run($workspace->fresh());
}

/** Le contrôle du type demandé DANS le batch donné. */
function clegalCheckOfType(LegalComplianceRunResult $result, InvestmentContractWorkspace $workspace, LegalCheckType $type): LegalComplianceCheck
{
    return $workspace->legalChecks()
        ->where('batch', $result->batch)
        ->where('type', $type->value)
        ->firstOrFail();
}

/**
 * Un acte significatif déposé : hors version/hashes du circuit
 * complet, l'existence du document suffit aux contrôles du
 * moteur juridique.
 */
function clegalDepositDocument(InvestmentContractWorkspace $workspace, ContractDocumentType $type): ContractDocument
{
    return ContractDocument::forceCreate([
        'investment_contract_workspace_id' => $workspace->id,
        'type' => $type->value,
        'title' => $type->label(),
        'status' => 'approved',
        'confidentiality' => 'shared',
        'current_version' => 1,
    ]);
}

it('asks for human review on a virgin workspace rather than inventing a legal form', function () {
    // Aucune entreprise, aucune composante : le moteur ne rejette
    // rien et ne présume rien — il réclame la fiche juridique.
    $workspace = clegalWorkspace();
    $result = clegalRunChecks($workspace);

    expect($result->blocking)->toBeFalse()
        ->and($result->statusCount(CheckStatus::NeedsReview))->toBeGreaterThan(0);

    $formCheck = clegalCheckOfType($result, $workspace, LegalCheckType::LegalFormCompatibility);
    expect($formCheck->status)->toBe(CheckStatus::NeedsReview)
        ->and($formCheck->severity->value)->toBe('medium')
        ->and($formCheck->message)->toContain('fiche juridique');

    // Composantes de financement absentes : les contrôles
    // conditionnés au financement restent hors objet.
    expect($workspace->legalChecks()->where('batch', $result->batch)->count())->toBeGreaterThan(0);
});

it('blocks closing on a missing company decision for a capital increase, then unblocks once the decision is documented', function () {
    [$company] = clegalCompanyWithRecord([
        'legal_form' => LegalForm::Sarlu->value,
        'share_count' => 300,
        'nominal_value_minor' => 100_000,
        'statutes_date' => now()->subMonth(),
        'representative_name' => 'Jean Mukendi',
        'representative_role' => 'gérant',
        'beneficial_owners' => [['name' => 'Jean Mukendi', 'share' => '100 %']],
    ]);
    $workspace = clegalWorkspace($company);

    ContractFinancingComponent::factory()->create([
        'investment_contract_workspace_id' => $workspace->id,
        'type' => 'capital',
        'equity_mechanism' => 'capital_increase',
    ]);

    $before = clegalRunChecks($workspace);
    $decision = clegalCheckOfType($before, $workspace, LegalCheckType::CorporateDecisionsExistence);

    // Contradiction de matière : un mouvement de capital sans
    // délibération sociétaire ne passe jamais en silence.
    expect($decision->status)->toBe(CheckStatus::Failed)
        ->and($decision->severity->value)->toBe('critical')
        ->and($decision->severity->blocksClosing())->toBeTrue()
        ->and($before->blocking)->toBeTrue()
        ->and(app(LegalComplianceService::class)->hasOpenBlockingChecks($workspace))->toBeTrue();

    // La décision sociale débloque la porte critique — l'acte
    // constitutif accompagnant le mécanisme est déposé aussi,
    // sinon la porte des formalités resterait levée.
    clegalDepositDocument($workspace, ContractDocumentType::CompanyDecision);
    clegalDepositDocument($workspace, ContractDocumentType::SubscriptionDeed);

    $after = clegalRunChecks($workspace);
    $decisionAfter = clegalCheckOfType($after, $workspace, LegalCheckType::CorporateDecisionsExistence);

    expect($decisionAfter->status)->toBe(CheckStatus::Passed)
        ->and(app(LegalComplianceService::class)->hasOpenBlockingChecks($workspace))->toBeFalse()
        ->and($after->blocking)->toBeFalse();
});

it('keeps the earlier batch blocking until the operation actually reruns its checks', function () {
    [$company] = clegalCompanyWithRecord(['legal_form' => LegalForm::Sarlu->value, 'share_count' => 300]);
    $workspace = clegalWorkspace($company);

    ContractFinancingComponent::factory()->create([
        'investment_contract_workspace_id' => $workspace->id,
        'type' => 'capital',
        'equity_mechanism' => 'capital_increase',
    ]);

    clegalRunChecks($workspace);
    expect(app(LegalComplianceService::class)->hasOpenBlockingChecks($workspace))->toBeTrue();

    // Les actes sont déposés mais la passe n'est pas relancée : le
    // moteur n'invente pas le rétablissement — le présent reste
    // celui de la dernière exécution.
    clegalDepositDocument($workspace, ContractDocumentType::CompanyDecision);
    clegalDepositDocument($workspace, ContractDocumentType::SubscriptionDeed);

    expect(app(LegalComplianceService::class)->hasOpenBlockingChecks($workspace))->toBeTrue();

    // La relance évalue le présent : plus aucune contradiction.
    $after = clegalRunChecks($workspace);

    expect($after->blocking)->toBeFalse()
        ->and(app(LegalComplianceService::class)->hasOpenBlockingChecks($workspace))->toBeFalse();
});

it('blocks closing while the deed constitutive of the title movement is missing', function () {
    [$company] = clegalCompanyWithRecord(['legal_form' => LegalForm::Sarlu->value, 'share_count' => 300]);
    $workspace = clegalWorkspace($company);

    ContractFinancingComponent::factory()->create([
        'investment_contract_workspace_id' => $workspace->id,
        'type' => 'capital',
        'equity_mechanism' => 'capital_increase',
    ]);

    $result = clegalRunChecks($workspace);
    $formalities = clegalCheckOfType($result, $workspace, LegalCheckType::FormalitiesCompleteness);

    expect($formalities->status)->toBe(CheckStatus::Failed)
        ->and($formalities->severity->value)->toBe('critical')
        ->and($formalities->message)->toContain('acte de souscription');

    // L'acte déposé débloque la porte des formalités : la décision
    // sociale du même mécanisme est déposée aussi, sinon la porte
    // des organes resterait levée.
    clegalDepositDocument($workspace, ContractDocumentType::SubscriptionDeed);
    clegalDepositDocument($workspace, ContractDocumentType::CompanyDecision);

    $after = clegalRunChecks($workspace);
    $formalitiesAfter = $workspace->legalChecks()
        ->where('batch', $after->batch)
        ->where('type', LegalCheckType::FormalitiesCompleteness->value)
        ->get();

    // Averti des autorisations sectorielles à documenter, mais
    // plus aucun échec critique dans la nouvelle passe.
    expect($formalitiesAfter->contains(fn ($check) => $check->status === CheckStatus::Passed))->toBeTrue()
        ->and($formalitiesAfter->contains(fn ($check) => $check->status === CheckStatus::Failed))->toBeFalse()
        ->and($after->blocking)->toBeFalse();
});

it('flags a unipersonal form recorded with a plural membership structure', function () {
    [$company, $record] = clegalCompanyWithRecord(
        ['legal_form' => LegalForm::Sarlu->value, 'share_count' => 300],
        [
            ['holder_name' => 'Jean Mukendi', 'share_count' => 200, 'percentage_bps' => 6600],
            ['holder_name' => 'Investisseur Alpha', 'share_count' => 100, 'percentage_bps' => 3300],
        ],
    );
    $workspace = clegalWorkspace($company);

    $result = clegalRunChecks($workspace);
    $divergence = clegalCheckOfType($result, $workspace, LegalCheckType::LegalFormCompatibility);

    expect($divergence->status)->toBe(CheckStatus::Warning)
        ->and($divergence->severity->value)->toBe('high')
        ->and($divergence->message)->toContain('divergence');

    // Une démarche d'avertissement ne fige jamais la clôture.
    expect($result->blocking)->toBeFalse();
});

it('fails title mechanism validity when recorded shares disagree with registered holders', function () {
    [$company] = clegalCompanyWithRecord(
        ['legal_form' => LegalForm::Sarlu->value, 'share_count' => 300],
        [
            ['holder_name' => 'Jean Mukendi', 'share_count' => 200, 'percentage_bps' => 6600],
        ],
    );
    $workspace = clegalWorkspace($company);

    $result = clegalRunChecks($workspace);
    $titles = clegalCheckOfType($result, $workspace, LegalCheckType::TitleMechanismValidity);

    expect($titles->status)->toBe(CheckStatus::Failed)
        ->and($titles->severity->value)->toBe('high')
        ->and($titles->message)->toContain('300 titre(s)')
        ->and($titles->details['holdings_sum'])->toBe(200);
});

it('asks for the RDC financial regulation review in a funded operation', function () {
    [$company] = clegalCompanyWithRecord(['legal_form' => LegalForm::Sarlu->value, 'share_count' => 300]);
    $workspace = clegalWorkspace($company);

    ContractFinancingComponent::factory()->create([
        'investment_contract_workspace_id' => $workspace->id,
        'type' => 'capital',
    ]);

    $result = clegalRunChecks($workspace);
    $regulation = clegalCheckOfType($result, $workspace, LegalCheckType::RdcFinancialRegulation);

    // La plateforme n'émet aucun avis BCC : conduit en revue humaine.
    expect($regulation->status)->toBe(CheckStatus::NeedsReview)
        ->and($regulation->severity->value)->toBe('medium');
});

it('keeps the closing gate open while a critical legal check stays unresolved', function () {
    [$company] = clegalCompanyWithRecord(['legal_form' => LegalForm::Sarlu->value, 'share_count' => 300]);
    $workspace = clegalWorkspace($company);

    ContractFinancingComponent::factory()->create([
        'investment_contract_workspace_id' => $workspace->id,
        'type' => 'capital',
        'equity_mechanism' => 'capital_increase',
    ]);

    clegalRunChecks($workspace);

    expect(app(LegalComplianceService::class)->hasOpenBlockingChecks($workspace))->toBeTrue();
});

it('blocks the close action through the legal gate', function () {
    [$company] = clegalCompanyWithRecord(['legal_form' => LegalForm::Sarlu->value, 'share_count' => 300]);
    $workspace = clegalWorkspace($company);

    ContractFinancingComponent::factory()->create([
        'investment_contract_workspace_id' => $workspace->id,
        'type' => 'capital',
        'equity_mechanism' => 'capital_increase',
    ]);

    clegalRunChecks($workspace);

    // Seule la porte juridique est levée : revue horodatée,
    // conditions toutes conduites, aucun contrôle financier.
    $workspace->forceFill([
        'legal_reviewed_at' => now(),
        'founder_share_bps' => 7500,
        'investor_share_bps' => 2500,
    ])->save();

    $founder = $workspace->dealRoom->entrepreneur;

    $blocked = null;

    try {
        app(CloseOperation::class)->handle($founder, $workspace->fresh());
    } catch (ValidationException $exception) {
        $blocked = collect($exception->errors())->flatten()->implode(' ');
    }

    expect($blocked)->toContain('conformité juridique');
});

it('runs both batch families from the single HTTP pass', function () {
    [$company] = clegalCompanyWithRecord(['legal_form' => LegalForm::Sarlu->value, 'share_count' => 300]);
    $workspace = clegalWorkspace($company);

    ContractFinancingComponent::factory()->create([
        'investment_contract_workspace_id' => $workspace->id,
        'type' => 'capital',
        'equity_mechanism' => 'capital_increase',
    ]);

    $founder = $workspace->dealRoom->entrepreneur;

    $this->actingAs($founder)
        ->post("/contract-workspaces/{$workspace->id}/financial-checks")
        ->assertRedirect();

    $financialBatches = FinancialConsistencyCheck::query()
        ->where('investment_contract_workspace_id', $workspace->id)
        ->distinct()->pluck('batch');
    $legalBatches = LegalComplianceCheck::query()
        ->where('investment_contract_workspace_id', $workspace->id)
        ->distinct()->pluck('batch');

    // Deux familles exécutables en même fois — chacune son lot,
    // chacun son identifiant, l'historique reste entier.
    expect($financialBatches)->toHaveCount(1)
        ->and($financialBatches->first())->toStartWith('fin-')
        ->and($legalBatches)->toHaveCount(1)
        ->and($legalBatches->first())->toStartWith('leg-');
});

it('returns the latest legal batch to the workspace page only', function () {
    [$company] = clegalCompanyWithRecord(['legal_form' => LegalForm::Sarlu->value, 'share_count' => 300]);
    $workspace = clegalWorkspace($company);

    ContractFinancingComponent::factory()->create([
        'investment_contract_workspace_id' => $workspace->id,
        'type' => 'capital',
    ]);

    // Deux passes successives : la page ne montre jamais un filet
    // d'époques mêlées, seulement la dernière passe par famille.
    clegalRunChecks($workspace);
    clegalRunChecks($workspace);

    $workspace = $workspace->fresh();
    $historyBatches = $workspace->legalChecks->pluck('batch')->unique();
    $payload = ContractWorkspaceData::fromModel($workspace, $workspace->dealRoom->entrepreneur);
    $batches = collect($payload->legalChecks)->pluck('batch')->unique();

    expect($historyBatches)->toHaveCount(2)
        ->and($batches)->toHaveCount(1)
        ->and($historyBatches)->toContain($batches->first())
        // La page ne sert que la dernière passe, jamais l'historique.
        ->and(collect($payload->legalChecks)->count())
        ->toBeLessThan($workspace->legalChecks->count());
});
