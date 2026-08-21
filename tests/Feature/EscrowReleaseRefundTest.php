<?php

use App\Actions\Escrow\RaiseDispute;
use App\Actions\Escrow\ReleaseEscrow;
use App\Actions\Escrow\RefundEscrow;
use App\Models\Escrow;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SubscriptionPlansSeeder::class);
});

it('lets the investor release held funds, moving escrow to Released', function () {
    $escrow = Escrow::factory()->held()->create();

    app(ReleaseEscrow::class)->handle($escrow->investor, $escrow);

    expect($escrow->fresh()->status->value)->toBe('released');
});

it('never lets release run twice on the same escrow, even called back-to-back', function () {
    $escrow = Escrow::factory()->held()->create();

    app(ReleaseEscrow::class)->handle($escrow->investor, $escrow);

    expect(fn () => app(ReleaseEscrow::class)->handle($escrow->investor, $escrow->fresh()))
        ->toThrow(\Illuminate\Validation\ValidationException::class);

    expect($escrow->fresh()->status->value)->toBe('released'); // unchanged by the second call
});

it('never lets the entrepreneur release or refund an escrow — only the investor may', function () {
    $escrow = Escrow::factory()->held()->create();

    expect($escrow->entrepreneur->can('release', $escrow))->toBeFalse();
    expect($escrow->entrepreneur->can('requestRefund', $escrow))->toBeFalse();
    expect($escrow->investor->can('release', $escrow))->toBeTrue();
    expect($escrow->investor->can('requestRefund', $escrow))->toBeTrue();
});

it('never lets someone outside the deal room view, release, refund, or dispute an escrow', function () {
    $escrow = Escrow::factory()->held()->create();
    $outsider = User::factory()->investor()->create();

    expect($outsider->can('view', $escrow))->toBeFalse();
    expect($outsider->can('release', $escrow))->toBeFalse();
    expect($outsider->can('requestRefund', $escrow))->toBeFalse();
    expect($outsider->can('dispute', $escrow))->toBeFalse();
});

it('actually calls the payment provider to refund, and only marks Refunded on a successful provider response', function () {
    $escrow = Escrow::factory()->held()->create();
    $payment = \App\Models\Payment::factory()->create([
        'payer_id' => $escrow->investor_user_id,
        'provider' => 'stripe',
        'provider_reference' => 'pi_refund_test',
        'amount_minor' => $escrow->amount_minor,
    ]);
    $escrow->update(['payment_id' => $payment->id]);

    Http::fake([
        'api.stripe.com/v1/refunds' => Http::response(['id' => 're_test_123', 'amount' => $escrow->amount_minor], 200),
    ]);

    app(RefundEscrow::class)->handle($escrow->investor, $escrow->fresh());

    $escrow->refresh();
    expect($escrow->status->value)->toBe('refunded');
    expect($escrow->refund_reference)->toBe('re_test_123');
});

it('leaves the escrow in refund_requested, not silently reverted, when the provider refund fails', function () {
    $escrow = Escrow::factory()->held()->create();
    $payment = \App\Models\Payment::factory()->create([
        'payer_id' => $escrow->investor_user_id,
        'provider' => 'stripe',
        'provider_reference' => 'pi_refund_fail',
    ]);
    $escrow->update(['payment_id' => $payment->id]);

    Http::fake([
        'api.stripe.com/v1/refunds' => Http::response(['error' => ['message' => 'card_declined']], 402),
    ]);

    expect(fn () => app(RefundEscrow::class)->handle($escrow->investor, $escrow->fresh()))
        ->toThrow(\Illuminate\Validation\ValidationException::class);

    expect($escrow->fresh()->status->value)->toBe('refund_requested');
});

it('blocks release once a dispute is open, until the dispute is resolved', function () {
    $escrow = Escrow::factory()->held()->create();

    app(RaiseDispute::class)->handle($escrow->entrepreneur, $escrow, 'Le produit livré ne correspond pas aux conditions.');

    expect($escrow->fresh()->status->value)->toBe('disputed');
    expect($escrow->investor->can('release', $escrow->fresh()))->toBeFalse();
});

it('never allows opening a second dispute while one is already open', function () {
    $escrow = Escrow::factory()->held()->create();

    app(RaiseDispute::class)->handle($escrow->investor, $escrow, 'First issue.');

    expect(fn () => app(RaiseDispute::class)->handle($escrow->entrepreneur, $escrow->fresh(), 'Second issue.'))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});
