<?php

use App\Actions\Escrow\RaiseDispute;
use App\Actions\Escrow\ResolveDispute;
use App\Models\Escrow;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

it('never lets a non-admin resolve a dispute', function () {
    $escrow = Escrow::factory()->held()->create();
    app(RaiseDispute::class)->handle($escrow->investor, $escrow, 'Issue.');

    expect($escrow->investor->can('resolve', \App\Models\Dispute::class))->toBeFalse();
    expect($escrow->entrepreneur->can('resolve', \App\Models\Dispute::class))->toBeFalse();
});

it('rejecting a dispute returns the escrow to Held, not to any terminal state', function () {
    $escrow = Escrow::factory()->held()->create();
    $dispute = app(RaiseDispute::class)->handle($escrow->investor, $escrow, 'Issue.');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    app(ResolveDispute::class)->handle($admin, $dispute, 'reject', 'No evidence of an actual issue.');

    expect($escrow->fresh()->status->value)->toBe('held');
    expect($dispute->fresh()->status->value)->toBe('rejected');
});

it('resolving a dispute in favor of release actually releases the funds', function () {
    $escrow = Escrow::factory()->held()->create();
    $dispute = app(RaiseDispute::class)->handle($escrow->entrepreneur, $escrow, 'Investor is stalling.');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    app(ResolveDispute::class)->handle($admin, $dispute, 'release', 'Deliverables confirmed complete.');

    expect($escrow->fresh()->status->value)->toBe('released');
    expect($dispute->fresh()->status->value)->toBe('resolved');
});

it('resolving a dispute in favor of refund actually calls the provider and refunds', function () {
    $escrow = Escrow::factory()->held()->create();
    $payment = \App\Models\Payment::factory()->create([
        'payer_id' => $escrow->investor_user_id,
        'provider' => 'stripe',
        'provider_reference' => 'pi_dispute_refund',
    ]);
    $escrow->update(['payment_id' => $payment->id]);

    $dispute = app(RaiseDispute::class)->handle($escrow->investor, $escrow->fresh(), 'Project never delivered.');

    Http::fake(['api.stripe.com/v1/refunds' => Http::response(['id' => 're_dispute_1', 'amount' => $escrow->amount_minor], 200)]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    app(ResolveDispute::class)->handle($admin, $dispute, 'refund', 'Entrepreneur failed to deliver per contract.');

    expect($escrow->fresh()->status->value)->toBe('refunded');
});
