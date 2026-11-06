<?php

use App\Actions\Contracts\CreateInvestmentContractWorkspace;
use App\Actions\Contracts\RecordAnalysisConsent;
use App\Contracts\NegotiationAnalysisProviderInterface;
use App\Enums\AiRequestStatus;
use App\Models\DealRoom;
use App\Models\InvestmentContractWorkspace;
use App\Models\NegotiationConversationSnapshot;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Ai\NegotiationAnalysisResult;
use App\Services\SubscriptionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

// Helpers nommés de façon unique pour cohabiter avec les autres
// fichiers de tests dans un même run de la suite.

function cwHttpRoom(): DealRoom
{
    $entrepreneur = User::factory()->entrepreneur()->create();
    $investor = User::factory()->investor()->create();

    return DealRoom::factory()->create([
        'entrepreneur_user_id' => $entrepreneur->id,
        'investor_user_id' => $investor->id,
    ]);
}

function cwHttpSubscribe(User $user, string $planKey): void
{
    app(SubscriptionService::class)->switchPlan(
        $user,
        SubscriptionPlan::query()->where('key', $planKey)->firstOrFail(),
    );
}

function cwHttpOpenedWorkspace(DealRoom $room): InvestmentContractWorkspace
{
    return app(CreateInvestmentContractWorkspace::class)
        ->handle($room->entrepreneur, $room);
}

function cwHttpConsented(InvestmentContractWorkspace $workspace): InvestmentContractWorkspace
{
    $room = $workspace->dealRoom;
    app(RecordAnalysisConsent::class)->handle($room->entrepreneur, $workspace);
    app(RecordAnalysisConsent::class)->handle($room->investor, $workspace);

    return $workspace->fresh();
}

/**
 * Premier message d'erreur de session pour une clé donnée — les
 * ValidationException Inertia deviennent une redirection, jamais une
 * exception propagée au test HTTP.
 */
function cwHttpFirstError(string $key): string
{
    $bag = session('errors');

    return (string) ($bag?->getBag('default')->first($key) ?? '');
}

/**
 * Fournisseur factice — jamais le provider réel laravel/ai côté HTTP.
 */
function cwHttpBindProvider(?NegotiationAnalysisResult $result): void
{
    app()->bind(NegotiationAnalysisProviderInterface::class, fn () => new class($result) implements NegotiationAnalysisProviderInterface
    {
        public function __construct(private readonly ?NegotiationAnalysisResult $result) {}

        public function analyze(
            InvestmentContractWorkspace $workspace,
            string $conversationTranscript,
            string $clauseCatalog,
            string $operationSummary,
        ): ?NegotiationAnalysisResult {
            return $this->result;
        }
    });
}

it('opens the workspace over HTTP for a subscriber participant and redirects to it', function () {
    $room = cwHttpRoom();
    cwHttpSubscribe($room->entrepreneur, 'entrepreneur');

    $this->actingAs($room->entrepreneur)
        ->post("/deal-rooms/{$room->id}/contract-workspace")
        ->assertRedirect()
        ->assertSessionHas('success');

    $workspace = InvestmentContractWorkspace::query()->where('deal_room_id', $room->id)->firstOrFail();

    // Re-tentative : une salle = une opération — la ValidationException
    // Inertia revient à la salle avec une erreur de session, jamais
    // une seconde création.
    $this->actingAs($room->entrepreneur)
        ->post("/deal-rooms/{$room->id}/contract-workspace")
        ->assertSessionHasErrors('deal_room_id');

    expect(cwHttpFirstError('deal_room_id'))->toContain('existe déjà')
        ->and(InvestmentContractWorkspace::query()->where('deal_room_id', $room->id)->count())->toBe(1);
});

it('refuses workspace opening over HTTP to outsiders', function () {
    $room = cwHttpRoom();
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->post("/deal-rooms/{$room->id}/contract-workspace")
        ->assertForbidden();

    expect(InvestmentContractWorkspace::query()->where('deal_room_id', $room->id)->exists())->toBeFalse();
});

it('rejects workspace opening over HTTP when the plan lacks the negotiation tools feature', function () {
    $room = cwHttpRoom();
    // Sans abonnement : le plan de secours ne porte pas
    // « negotiation_tools » — la policy passe, le gating refuse,
    // piloté par les données de plan et jamais par un rôle codé en dur.

    $this->actingAs($room->entrepreneur)
        ->post("/deal-rooms/{$room->id}/contract-workspace")
        ->assertSessionHasErrors('workspace');

    expect(cwHttpFirstError('workspace'))->toContain('outils de négociation');
    expect(InvestmentContractWorkspace::query()->where('deal_room_id', $room->id)->exists())->toBeFalse();
});

it('shows the workspace page to participants and keeps outsiders out', function () {
    $room = cwHttpRoom();
    cwHttpSubscribe($room->entrepreneur, 'entrepreneur');
    $workspace = cwHttpOpenedWorkspace($room);

    $this->actingAs($room->investor)
        ->get("/contract-workspaces/{$workspace->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Contracts/Workspace')
            ->has('workspace', fn ($ws) => $ws
                ->where('id', $workspace->id)
                ->where('dealRoomId', $room->id)
                ->where('status', 'draft')
                ->where('viewerRole', 'investor')
                ->where('bothConsented', false)
                ->has('terms')
                ->has('clauses')
                ->has('snapshots')
                ->has('financingComponents')
                ->etc()));

    $outsider = User::factory()->create();
    $this->actingAs($outsider)
        ->get("/contract-workspaces/{$workspace->id}")
        ->assertForbidden();
});

it('records consent over HTTP for each side', function () {
    $room = cwHttpRoom();
    cwHttpSubscribe($room->entrepreneur, 'entrepreneur');
    $workspace = cwHttpOpenedWorkspace($room);

    $this->actingAs($room->investor)
        ->post("/contract-workspaces/{$workspace->id}/consent")
        ->assertSessionHas('success');

    $workspace->refresh();
    expect($workspace->investor_analysis_consent_at)->not->toBeNull()
        ->and($workspace->conversationAnalysisConsented())->toBeFalse();

    $this->actingAs($room->entrepreneur)
        ->post("/contract-workspaces/{$workspace->id}/consent")
        ->assertSessionHas('success');

    expect($workspace->fresh()->conversationAnalysisConsented())->toBeTrue();
});

it('runs the ai analysis over HTTP with a fake provider and produces a completed snapshot', function () {
    $room = cwHttpRoom();
    cwHttpSubscribe($room->entrepreneur, 'entrepreneur');
    $workspace = cwHttpConsented(cwHttpOpenedWorkspace($room));
    cwHttpBindProvider(new NegotiationAnalysisResult(
        'Synthèse HTTP de test.',
        [],
        [],
        500,
    ));

    $this->actingAs($room->investor)
        ->post("/contract-workspaces/{$workspace->id}/ai-analysis")
        ->assertSessionHas('success');

    $snapshot = NegotiationConversationSnapshot::query()
        ->where('investment_contract_workspace_id', $workspace->id)
        ->firstOrFail();

    expect($snapshot->status)->toBe(AiRequestStatus::Completed)
        ->and((string) data_get($snapshot->synthesis, 'summary'))->toBe('Synthèse HTTP de test.')
        ->and($snapshot->created_by_user_id)->toBe($room->investor_user_id);
});

it('marks the snapshot as failed over HTTP and surfaces the failure without any fabricated analysis', function () {
    $room = cwHttpRoom();
    cwHttpSubscribe($room->entrepreneur, 'entrepreneur');
    $workspace = cwHttpConsented(cwHttpOpenedWorkspace($room));
    cwHttpBindProvider(null);

    $this->actingAs($room->investor)
        ->post("/contract-workspaces/{$workspace->id}/ai-analysis")
        ->assertSessionHasErrors('ai');

    $snapshot = NegotiationConversationSnapshot::query()
        ->where('investment_contract_workspace_id', $workspace->id)
        ->firstOrFail();

    expect($snapshot->status)->toBe(AiRequestStatus::Failed)
        ->and((string) $snapshot->failure_reason)->toContain('relance')
        // La vraie synthèse ne remplace jamais un échec : point de
        // re-fabrication (prompt §4).
        ->and($snapshot->synthesis)->toBeNull();
});
