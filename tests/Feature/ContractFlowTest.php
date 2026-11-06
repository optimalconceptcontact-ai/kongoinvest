<?php

use App\Actions\Contracts\GenerateContract;
use App\Actions\Contracts\RespondToContract;
use App\Models\Contract;
use App\Models\DealRoom;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

it('generates a contract with an immutable first version and the required legal disclaimer', function () {
    $dealRoom = DealRoom::factory()->create();

    $contract = app(GenerateContract::class)->handle($dealRoom->entrepreneur, $dealRoom, [
        'investment_type' => 'equity',
        'amount_minor' => 50_000_00,
        'currency' => 'USD',
    ]);

    expect($contract->status->value)->toBe('generated');
    expect($contract->versions()->count())->toBe(1);

    // Spec §47: never presented as a universally legally valid document.
    expect($contract->latestVersion()->content['disclaimer'])->toContain('ne constitue pas un contrat juridiquement validé');
});

it('only reaches Accepted once BOTH the entrepreneur and the investor have accepted', function () {
    $dealRoom = DealRoom::factory()->create();
    $contract = app(GenerateContract::class)->handle($dealRoom->entrepreneur, $dealRoom, [
        'investment_type' => 'equity', 'amount_minor' => 10_000_00, 'currency' => 'USD',
    ]);

    $responder = app(RespondToContract::class);

    $responder->accept($dealRoom->entrepreneur, $contract);
    expect($contract->fresh()->status->value)->toBe('generated'); // only one side so far

    $responder->accept($dealRoom->investor, $contract);
    expect($contract->fresh()->status->value)->toBe('accepted'); // both sides now
});

it('never lets someone outside the deal room generate, accept, or reject its contract', function () {
    $dealRoom = DealRoom::factory()->create();
    $outsider = User::factory()->investor()->create();

    $contract = app(GenerateContract::class)->handle($dealRoom->entrepreneur, $dealRoom, [
        'investment_type' => 'equity', 'amount_minor' => 10_000_00, 'currency' => 'USD',
    ]);

    expect($outsider->can('generate', [Contract::class, $dealRoom]))->toBeFalse();
    expect($outsider->can('accept', $contract))->toBeFalse();
    expect($outsider->can('reject', $contract))->toBeFalse();
});
