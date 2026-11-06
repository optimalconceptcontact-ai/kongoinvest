<?php

namespace App\Actions\Payments;

use App\Enums\PaymentProviderName;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\PaymentIntentResult;
use App\Services\Payments\PaymentProviderFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreatePayment
{
    public function __construct(private PaymentProviderFactory $providers) {}

    /**
     * @param  PaymentPurpose  $purpose
     * @param  PaymentProviderName  $provider
     * @return array{0: Payment, 1: PaymentIntentResult}
     */
    public function handle(
        User $payer,
        $purpose,
        $provider,
        int $amountMinor,
        string $currency,
        ?Model $payable = null,
        array $metadata = [],
        ?string $idempotencyKey = null,
    ): array {
        return DB::transaction(function () use ($payer, $purpose, $provider, $amountMinor, $currency, $payable, $metadata, $idempotencyKey) {
            // payer_id/purpose/payable/provider/amount_minor/currency/
            // idempotency_key are deliberately outside Payment::$fillable
            // (only this action may set them) — create() would silently
            // drop them, so forceCreate() bypasses that protection here.
            $payment = Payment::forceCreate([
                'payer_id' => $payer->id,
                'purpose' => $purpose->value,
                'payable_type' => $payable?->getMorphClass(),
                'payable_id' => $payable?->getKey(),
                'provider' => $provider->value,
                'amount_minor' => $amountMinor,
                'currency' => $currency,
                'status' => PaymentStatus::Pending->value,
                // Caller-supplied idempotency key (e.g. derived from the
                // checkout form's own request ID) takes priority; falls
                // back to a fresh UUID so this action is always safe to
                // call even without one — but a caller that wants true
                // retry-safety across HTTP requests must supply its own
                // (spec §41).
                'idempotency_key' => $idempotencyKey ?? (string) Str::uuid(),
                'metadata' => $metadata,
            ]);

            $intent = $this->providers->make($provider)->createPayment($payment);

            return [$payment->fresh(), $intent];
        });
    }
}
