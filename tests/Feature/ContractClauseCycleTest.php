<?php

use App\Actions\Contracts\CreateInvestmentContractWorkspace;
use App\Actions\Contracts\ProposeClause;
use App\Actions\Contracts\RecordClauseDecision;
use App\Enums\ClauseCategory;
use App\Enums\ClauseDecision;
use App\Enums\ClauseStatus;
use App\Enums\EquityMechanism;
use App\Enums\FinancingComponentType;
use App\Enums\WorkspaceStatus;
use App\Models\AuditLog;
use App\Models\ContractClauseApproval;
use App\Models\ContractClauseLibrary;
use App\Models\ContractClauseSelection;
use App\Models\ContractFinancingComponent;
use App\Models\DealRoom;
use App\Models\InvestmentContractWorkspace;
use App\Models\SubscriptionPlan;
use App\Models\User;
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

function cclSubscribe(User $user, string $planKey): void
{
    app(SubscriptionService::class)->switchPlan(
        $user,
        SubscriptionPlan::query()->where('key', $planKey)->firstOrFail(),
    );
}

function cclRoom(): DealRoom
{
    $entrepreneur = User::factory()->entrepreneur()->create();
    $investor = User::factory()->investor()->create();

    return DealRoom::factory()->create([
        'entrepreneur_user_id' => $entrepreneur->id,
        'investor_user_id' => $investor->id,
    ]);
}

function cclWorkspace(): InvestmentContractWorkspace
{
    $room = cclRoom();
    cclSubscribe($room->entrepreneur, 'entrepreneur');

    return app(CreateInvestmentContractWorkspace::class)->handle($room->entrepreneur, $room);
}

/**
 * @return array{0: ContractClauseSelection, 1: InvestmentContractWorkspace}
 */
function cclLibrarySelection(InvestmentContractWorkspace $workspace): array
{
    // Une composante en capital pour l'éligibilité instrument.
    ContractFinancingComponent::forceCreate([
        'investment_contract_workspace_id' => $workspace->id,
        'type' => FinancingComponentType::Capital->value,
        'equity_mechanism' => EquityMechanism::CapitalIncrease->value,
        'amount_minor' => 100_000_00,
        'currency' => $workspace->currency,
        'is_primary' => true,
        'sort_order' => 0,
    ]);

    $library = ContractClauseLibrary::create([
        'slug' => 'clause-'.fake()->unique()->slug(),
        'title' => 'Clause de test',
        'category' => ClauseCategory::Capital->value,
        'purpose' => 'Protéger le capital pour cette opération de test.',
        'plain_language_explanation' => 'Explication en langage simple.',
        'founder_benefit' => 'Bénéfice fondateur.',
        'investor_benefit' => 'Bénéfice investisseur.',
        'financial_impact' => 'Impact financier non évalué en test.',
        'legal_impact' => 'Impact juridique non évalué en test.',
        'risks' => 'Aucun risque réaliste en test.',
        'body' => 'Corps contractuel de base.',
        'variants' => [
            ['key' => 'large', 'brief' => 'Portée large', 'body' => 'Corps variante large.'],
        ],
        'is_active' => true,
    ]);

    $selection = app(ProposeClause::class)->handle($workspace->dealRoom->investor, $workspace, [
        'source' => 'library',
        'library_id' => $library->id,
    ]);

    return [$selection, $workspace];
}

function cclRun(User $user, InvestmentContractWorkspace $workspace, ContractClauseSelection $selection, array $input): ?string
{
    try {
        app(RecordClauseDecision::class)->handle($user, $workspace, $selection, $input);

        return null;
    } catch (ValidationException $exception) {
        return collect($exception->errors())->flatten()->implode(' ');
    }
}

it('proposes a library clause copying the exact body and never rewriting it', function () {
    $workspace = cclWorkspace();
    [$selection] = cclLibrarySelection($workspace);

    expect($selection->origin->value)->toBe('investor')
        ->and($selection->status)->toBe(ClauseStatus::ProposedByInvestor)
        ->and($selection->body)->toBe('Corps contractuel de base.')
        ->and($selection->is_custom)->toBeFalse();

    // Proposition de variante explicite : seule la variante
    // enregistrée fournit son texte.
    $investor = $workspace->dealRoom->investor;
    $second = ContractClauseLibrary::create([
        'slug' => 'clause-'.fake()->unique()->slug(),
        'title' => 'Seconde clause',
        'category' => ClauseCategory::General->value,
        'purpose' => 'Autre clause de test indépendante.',
        'plain_language_explanation' => 'Autre explication simple.',
        'founder_benefit' => 'Bénéfice fondateur.',
        'investor_benefit' => 'Bénéfice investisseur.',
        'financial_impact' => 'Impact financier test.',
        'legal_impact' => 'Impact juridique test.',
        'risks' => 'Aucun risque test.',
        'body' => 'Corps contractuel de base.',
        'variants' => [['key' => 'large', 'brief' => 'Portée large', 'body' => 'Corps variante large.']],
        'is_active' => true,
    ]);

    $variantSelection = app(ProposeClause::class)->handle($investor, $workspace, [
        'source' => 'library',
        'library_id' => $second->id,
        'variant' => 'large',
    ]);

    expect($variantSelection->body)->toBe('Corps variante large.')
        ->and($variantSelection->variant)->toBe('large');
});

it('refuses a custom clause without text and an unknown library entry', function () {
    $workspace = cclWorkspace();
    $founder = $workspace->dealRoom->entrepreneur;

    expect(function () use ($founder, $workspace) {
        app(ProposeClause::class)->handle($founder, $workspace, ['source' => 'custom', 'title' => 'Trop court']);
    })->toThrow(ValidationException::class);

    try {
        app(ProposeClause::class)->handle($founder, $workspace, [
            'source' => 'library',
            'library_id' => 999999,
        ]);
        $this->fail('La clause inconnue doit être refusée.');
    } catch (ValidationException $exception) {
        expect(collect($exception->errors())->flatten()->implode(' '))
            ->toContain('jamais de proposition inventée');
    }
});

it('derives the clause status from the latest decision of each side', function () {
    $workspace = cclWorkspace();
    [$selection, $workspace] = cclLibrarySelection($workspace);

    $founder = $workspace->dealRoom->entrepreneur;
    $investor = $workspace->dealRoom->investor;

    // Le côté qui a proposé ne tranche pas en premier.
    expect(cclRun($investor, $workspace, $selection, ['decision' => 'accept']))
        ->toContain('propre proposition');

    // Première décision (hors proposant) : le fondateur contre-propose.
    app(RecordClauseDecision::class)->handle($founder, $workspace, $selection, [
        'decision' => 'counter_proposal',
        'body' => 'Contre-texte du fondateur.',
    ]);
    expect($selection->fresh()->status)->toBe(ClauseStatus::InNegotiation);

    // Alternance : le fondateur ne peut pas répliquer deux fois de suite.
    expect(cclRun($founder, $workspace, $selection, ['decision' => 'accept']))
        ->toContain('alternent');

    // L'investisseur refuse la contre-proposition : dernier mot = refus.
    app(RecordClauseDecision::class)->handle($investor, $workspace, $selection, ['decision' => 'refuse']);
    expect($selection->fresh()->status)->toBe(ClauseStatus::Refused);
});

it('reaches accepted_by_both only with a double take recorded in approvals', function () {
    $workspace = cclWorkspace();
    [$selection, $workspace] = cclLibrarySelection($workspace);

    $founder = $workspace->dealRoom->entrepreneur;
    $investor = $workspace->dealRoom->investor;

    app(RecordClauseDecision::class)->handle($founder, $workspace, $selection, ['decision' => 'accept']);
    app(RecordClauseDecision::class)->handle($investor, $workspace, $selection, ['decision' => 'accept']);

    $fresh = $selection->fresh();
    expect($fresh->status)->toBe(ClauseStatus::AcceptedByBoth)
        ->and($fresh->isAcceptedByBoth())->toBeTrue()
        ->and(ContractClauseApproval::query()->where('contract_clause_selection_id', $selection->id)->count())->toBe(2)
        // Le mot standard trace les côtés, pas l'interface.
        ->and($fresh->founder_decision)->toBe(ClauseDecision::Accept)
        ->and($fresh->investor_decision)->toBe(ClauseDecision::Accept)
        ->and(AuditLog::query()->where('action', 'workspace.clause_decided')->count())->toBe(2);

    // Une clause convenue est définitive : plus de decision.
    expect(cclRun($investor, $workspace, $fresh, ['decision' => 'refuse']))
        ->toContain('définitivement acceptée');
});

it('carries counter proposals into in_negotiation with the counter text kept in approvals', function () {
    $workspace = cclWorkspace();
    [$selection, $workspace] = cclLibrarySelection($workspace);

    $founder = $workspace->dealRoom->entrepreneur;
    $investor = $workspace->dealRoom->investor;

    app(RecordClauseDecision::class)->handle($founder, $workspace, $selection, [
        'decision' => 'counter_proposal',
        'body' => 'Contre-texte du fondateur.',
        'comment' => 'Portée trop large.',
    ]);

    $fresh = $selection->fresh();
    expect($fresh->status)->toBe(ClauseStatus::InNegotiation)
        // Le corps de la sélection reste un instantané : le
        // contre-texte vit dans les approvals, JAMAIS en surcharge.
        ->and($fresh->body)->toBe('Corps contractuel de base.')
        ->and($fresh->founder_decision)->toBe(ClauseDecision::CounterProposal);

    $approval = ContractClauseApproval::query()->where('contract_clause_selection_id', $selection->id)->first();
    expect($approval->body)->toBe('Contre-texte du fondateur.')
        ->and($approval->comment)->toBe('Portée trop large.');

    app(RecordClauseDecision::class)->handle($investor, $workspace, $fresh, ['decision' => 'request_revision']);
    expect($fresh->fresh()->status)->toBe(ClauseStatus::ToRevise);
});

it('keeps clauses open to participants on active operations only', function () {
    $workspace = cclWorkspace();
    [$selection, $workspace] = cclLibrarySelection($workspace);

    $outsider = User::factory()->create();
    expect(cclRun($outsider, $workspace, $selection, ['decision' => 'accept']))
        ->toContain('participants');

    $workspace->forceFill(['status' => WorkspaceStatus::Cancelled->value])->save();
    expect(cclRun($workspace->dealRoom->investor, $workspace->fresh(), $selection, ['decision' => 'accept']))
        ->toContain('clôturée ou annulée');
});
