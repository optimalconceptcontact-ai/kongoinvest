<?php

use App\Actions\Contracts\CreateInvestmentContractWorkspace;
use App\Actions\Contracts\RecordAnalysisConsent;
use App\Enums\SubscriptionFeature;
use App\Enums\WorkspaceStatus;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\DealRoom;
use App\Models\InvestmentContractWorkspace;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

function workspacePlan(string $key): SubscriptionPlan
{
    return SubscriptionPlan::query()->where('key', $key)->firstOrFail();
}

function subscribeUserToPlan(User $user, string $planKey): void
{
    app(SubscriptionService::class)->switchPlan($user, workspacePlan($planKey));
}

function workScenarioRoom(): DealRoom
{
    $entrepreneur = User::factory()->entrepreneur()->create();
    $investor = User::factory()->investor()->create();

    return DealRoom::factory()->create([
        'entrepreneur_user_id' => $entrepreneur->id,
        'investor_user_id' => $investor->id,
    ]);
}

function openWorkspace(DealRoom $room): InvestmentContractWorkspace
{
    return app(CreateInvestmentContractWorkspace::class)
        ->handle($room->entrepreneur, $room);
}

function consentExceptionMessage(User $user, InvestmentContractWorkspace $workspace): ?string
{
    try {
        app(RecordAnalysisConsent::class)->handle($user, $workspace);

        return null;
    } catch (ValidationException $exception) {
        return collect($exception->errors())->flatten()->implode(' ');
    }
}

it('opens a contract workspace from a deal room once and only once', function () {
    $room = workScenarioRoom();
    subscribeUserToPlan($room->entrepreneur, 'entrepreneur');

    $workspace = openWorkspace($room);

    expect($workspace->deal_room_id)->toBe($room->id);
    expect($workspace->created_by_user_id)->toBe($room->entrepreneur_user_id);
    expect($workspace->status)->toBe(WorkspaceStatus::Draft);
    expect($workspace->currency)->toBe('USD'); // défaut du scénario de référence §7
    expect($workspace->last_activity_at)->not->toBeNull();

    // Une salle = une opération : la seconde ouverture est refusée —
    // le garde-fou applicatif, avec l'index unique en ceinture de
    // sécurité (prompt §2).
    expect(fn () => openWorkspace($room))->toThrow(ValidationException::class);
    expect(InvestmentContractWorkspace::query()->where('deal_room_id', $room->id)->count())->toBe(1);

    // Tracé d'audit : qui a ouvert l'espace, sur quelle salle.
    expect(AuditLog::query()->where('action', 'contract_workspace.created')->count())->toBe(1);
});

it('gates workspace creation behind the negotiation tools subscription feature', function () {
    $room = workScenarioRoom();

    // Sans abonnement → plan Free de secours : aucune fonction → refus.
    $message = null;
    try {
        app(CreateInvestmentContractWorkspace::class)->handle($room->entrepreneur, $room);
    } catch (ValidationException $exception) {
        $message = collect($exception->errors())->flatten()->implode(' ');
    }

    expect($message)->not->toBeNull();
    expect($message)->toContain('outils de négociation');
    expect(InvestmentContractWorkspace::query()->where('deal_room_id', $room->id)->exists())->toBeFalse();

    // Abonnement payé mais SANS la fonction (plan Investor ne porte
    // pas « negotiation_tools ») → toujours refusé, gating piloté
    // par les données de plan et non par le prix.
    subscribeUserToPlan($room->investor, 'investor');
    expect(app(SubscriptionService::class)->hasFeature($room->investor, SubscriptionFeature::NegotiationTools))->toBeFalse();
    expect(fn () => app(CreateInvestmentContractWorkspace::class)->handle($room->investor, $room))->toThrow(ValidationException::class);
});

it('allows a subscriber holding the feature to attach a company to the workspace', function () {
    $room = workScenarioRoom();
    subscribeUserToPlan($room->entrepreneur, 'entrepreneur');

    $company = Company::factory()->create();
    $workspace = app(CreateInvestmentContractWorkspace::class)->handle($room->entrepreneur, $room, [
        'company_id' => $company->id,
    ]);

    expect($workspace->company_id)->toBe($company->id);
});

it('records traced consent from each party before the conversation may be analysed', function () {
    $room = workScenarioRoom();
    subscribeUserToPlan($room->entrepreneur, 'entrepreneur');
    $workspace = openWorkspace($room);

    // Un seul consentement ne débloque jamais l'analyse IA (§14).
    $workspace = app(RecordAnalysisConsent::class)->handle($room->entrepreneur, $workspace);
    expect($workspace->entrepreneur_analysis_consent_at)->not->toBeNull();
    expect($workspace->conversationAnalysisConsented())->toBeFalse();

    // Le double consentement débloque exactement à la seconde partie.
    $workspace = app(RecordAnalysisConsent::class)->handle($room->investor, $workspace);
    expect($workspace->conversationAnalysisConsented())->toBeTrue();

    // Tracé : un audit par partie consentante.
    $audits = AuditLog::query()->where('action', 'workspace.ai_analysis_consent')->get();
    expect($audits)->toHaveCount(2);
    expect($audits->pluck('metadata.side')->sort()->values()->all())->toBe(['entrepreneur', 'investor']);

    // Idempotent : re-consentir n'ajoute ni horodatage ni audit.
    app(RecordAnalysisConsent::class)->handle($room->entrepreneur, $workspace);
    expect(AuditLog::query()->where('action', 'workspace.ai_analysis_consent')->count())->toBe(2);
});

it('refuses consent from non-participants and on closed or cancelled operations', function () {
    $room = workScenarioRoom();
    subscribeUserToPlan($room->entrepreneur, 'entrepreneur');
    $workspace = openWorkspace($room);

    $outsider = User::factory()->create();
    expect(consentExceptionMessage($outsider, $workspace))->toContain('participants');

    $workspace->forceFill(['status' => WorkspaceStatus::Closed->value, 'closed_at' => now()])->save();
    expect(consentExceptionMessage($room->investor, $workspace))->toContain('clôturée ou annulée');
});

it('enforces the policy: participants see and act, outsiders stay out', function () {
    $room = workScenarioRoom();
    subscribeUserToPlan($room->entrepreneur, 'entrepreneur');
    $workspace = openWorkspace($room);

    // Les deux participants accèdent — la policy porte aussi l'accès
    // à l'espace au-delà de la seule salle (canal « contracts.view »).
    expect(Gate::forUser($room->entrepreneur)->allows('view', $workspace))->toBeTrue();
    expect(Gate::forUser($room->investor)->allows('view', $workspace))->toBeTrue();

    $outsider = User::factory()->create();
    expect(Gate::forUser($outsider)->denies('view', $workspace))->toBeTrue();
    expect(Gate::forUser($outsider)->denies('update', $workspace))->toBeTrue();
    expect(Gate::forUser($outsider)->denies('consent', $workspace))->toBeTrue();

    // L'admin de plateforme « users.manage » garde l'accès en lecture.
    $admin = User::factory()->create();
    $admin->givePermissionTo('users.manage');
    expect(Gate::forUser($admin)->allows('view', $workspace))->toBeTrue();

    // Ouverture d'un espace : réservée aux participants de la salle.
    // L'ability « create » se résout sur la CLASSE (convention Gate
    // de la maison : authorize('create', Project::class)), avec la
    // salle en argument de policy.
    expect(Gate::forUser($outsider)->denies('create', [InvestmentContractWorkspace::class, $room]))->toBeTrue();
    expect(Gate::forUser($room->investor)->allows('create', [InvestmentContractWorkspace::class, $room]))->toBeTrue();
});
