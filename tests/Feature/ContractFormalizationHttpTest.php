<?php

use App\Actions\Contracts\CreateInvestmentContractWorkspace;
use App\Enums\ContractDocumentType;
use App\Enums\WorkspaceStatus;
use App\Models\DealRoom;
use App\Models\InvestmentContractWorkspace;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

// Helpers HTTP préfixés « cfx » pour cohabiter avec les autres
// fichiers de la suite dans un même run.

function cfxRoom(): DealRoom
{
    $entrepreneur = User::factory()->entrepreneur()->create();
    $investor = User::factory()->investor()->create();

    return DealRoom::factory()->create([
        'entrepreneur_user_id' => $entrepreneur->id,
        'investor_user_id' => $investor->id,
    ]);
}

function cfxWorkspace(): InvestmentContractWorkspace
{
    $room = cfxRoom();
    app(SubscriptionService::class)->switchPlan(
        $room->entrepreneur,
        SubscriptionPlan::query()->where('key', 'entrepreneur')->firstOrFail(),
    );

    return app(CreateInvestmentContractWorkspace::class)->handle($room->entrepreneur, $room);
}

function cfxFirstError(string $key): string
{
    $bag = session('errors');

    return (string) ($bag?->getBag('default')->first($key) ?? '');
}

it('deposits a document over HTTP and returns the full approval flow to a signed document', function () {
    $workspace = cfxWorkspace();
    $founder = $workspace->dealRoom->entrepreneur;
    $investor = $workspace->dealRoom->investor;

    $this->actingAs($founder)
        ->post("/contract-workspaces/{$workspace->id}/documents", [
            'type' => ContractDocumentType::SubscriptionDeed->value,
            'title' => 'Bulletins de souscription',
            'content' => ['montant_minor' => 300_000_00],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $document = $workspace->documents()->firstOrFail();

    // Un tiers est refusé avant tout traitement.
    $this->actingAs(User::factory()->create())
        ->post("/contract-workspaces/{$workspace->id}/documents", [
            'type' => ContractDocumentType::TermSheet->value,
            'title' => 'Term sheet tiers',
            'content' => ['clé' => 'valeur'],
        ])->assertForbidden();

    $this->actingAs($investor)
        ->post("/contract-workspaces/{$workspace->id}/documents/{$document->id}/approve")
        ->assertRedirect()
        ->assertSessionHas('success');
    $this->actingAs($founder)
        ->post("/contract-workspaces/{$workspace->id}/documents/{$document->id}/approve")
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($document->fresh()->status->value)->toBe('approved');
});

it('guards the closing endpoints over HTTP and returns the external reason as session error', function () {
    $workspace = cfxWorkspace();
    $founder = $workspace->dealRoom->entrepreneur;

    // Les trois portes manquent : sur un espace vierge, la revue
    // juridique est la première porte refusée. L'explication sort
    // en erreur de session (Inertia back), jamais un succès
    // mensonger. (L'ordre des portes est couvert par les tests
    // d'action dans ContractFormalizationTest.)
    $this->actingAs($founder)
        ->post("/contract-workspaces/{$workspace->id}/close")
        ->assertRedirect()
        ->assertSessionHasErrors('workspace');

    expect(cfxFirstError('workspace'))->toContain('revue juridique');

    // Annulation : état terminal distinct, refus de re-passage.
    $this->actingAs($founder)
        ->post("/contract-workspaces/{$workspace->id}/cancel")
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($workspace->fresh()->status)->toBe(WorkspaceStatus::Cancelled);

    // La policy refuse d'emblée les participants sur une opération
    // terminée (clôturée ou annulée) : 403, pas un succès mensonger.
    $this->actingAs($founder)
        ->post("/contract-workspaces/{$workspace->id}/close")
        ->assertForbidden();

    $this->actingAs($founder)
        ->post("/contract-workspaces/{$workspace->id}/cancel")
        ->assertForbidden();
});
