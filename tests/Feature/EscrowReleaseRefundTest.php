<?php

use App\Actions\Escrow\RaiseDispute;
use App\Actions\Escrow\RefundEscrow;
use App\Actions\Escrow\ReleaseEscrow;
use App\Contracts\PaymentProviderInterface;
use App\Enums\PaymentProviderName;
use App\Enums\PaymentStatus;
use App\Models\Escrow;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\PaymentIntentResult;
use App\Services\Payments\PaymentProviderFactory;
use App\Services\Payments\RefundResult;
use App\Services\Payments\WebhookEvent;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

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
        ->toThrow(ValidationException::class);

    expect($escrow->fresh()->status->value)->toBe('released'); // unchanged by the second call
});

it('never lets the entrepreneur release or refund an escrow — only the investor may', function () {
    $escrow = Escrow::factory()->held()->create();
    $escrow->investor->assignRole('investor');
    $escrow->entrepreneur->assignRole('entrepreneur');

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
    $payment = Payment::factory()->create([
        'payer_id' => $escrow->investor_user_id,
        'provider' => 'stripe',
        'provider_reference' => 'pi_refund_test',
        'amount_minor' => $escrow->amount_minor,
    ]);
    $escrow->forceFill(['payment_id' => $payment->id])->save();

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
    $payment = Payment::factory()->create([
        'payer_id' => $escrow->investor_user_id,
        'provider' => 'stripe',
        'provider_reference' => 'pi_refund_fail',
    ]);
    $escrow->forceFill(['payment_id' => $payment->id])->save();

    Http::fake([
        'api.stripe.com/v1/refunds' => Http::response(['error' => ['message' => 'card_declined']], 402),
    ]);

    expect(fn () => app(RefundEscrow::class)->handle($escrow->investor, $escrow->fresh()))
        ->toThrow(ValidationException::class);

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
        ->toThrow(ValidationException::class);
});

it('never overwrites a dispute opened while the provider refund was in flight', function () {
    $escrow = Escrow::factory()->held()->create();
    $payment = Payment::factory()->create([
        'payer_id' => $escrow->investor_user_id,
        'provider' => 'stripe',
        'provider_reference' => 'pi_refund_race',
        'amount_minor' => $escrow->amount_minor,
    ]);
    $escrow->forceFill(['payment_id' => $payment->id])->save();

    // A provider whose refund call opens a dispute mid-flight, simulating
    // the concurrent entrepreneur request the split-transaction window
    // allows. The refund itself succeeds at the provider.
    $disputingEntrepreneur = $escrow->entrepreneur;
    app()->instance(PaymentProviderFactory::class, new class($disputingEntrepreneur, $escrow) extends PaymentProviderFactory
    {
        public function __construct(private User $raiser, private Escrow $target) {}

        public function make(PaymentProviderName $provider): PaymentProviderInterface
        {
            return new class($this->raiser, $this->target) implements PaymentProviderInterface
            {
                public function __construct(private User $raiser, private Escrow $target) {}

                public function createPayment(Payment $payment): PaymentIntentResult
                {
                    throw new RuntimeException('Not implemented in test double.');
                }

                public function verifyPayment(Payment $payment): PaymentStatus
                {
                    return $payment->status;
                }

                public function refund(Payment $payment, ?int $amountMinor = null): RefundResult
                {
                    app(RaiseDispute::class)->handle($this->raiser, $this->target->fresh(), 'Litige ouvert pendant le remboursement.');

                    return new RefundResult(successful: true, providerRefundId: 're_disputed_123', amountMinor: $payment->amount_minor);
                }

                public function getStatus(Payment $payment): PaymentStatus
                {
                    return $payment->status;
                }

                public function handleWebhook(Request $request): ?WebhookEvent
                {
                    return null;
                }
            };
        }
    });

    expect(fn () => app(RefundEscrow::class)->handle($escrow->investor, $escrow->fresh()))
        ->toThrow(ValidationException::class);

    $fresh = $escrow->fresh();
    expect($fresh->status->value)->toBe('disputed');
    expect($fresh->openDispute())->not->toBeNull();
    expect($fresh->events()->where('to_status', 'refunded')->exists())->toBeFalse();
});
