<?php

namespace App\Listeners;

use App\Enums\EscrowStatus;
use App\Events\EscrowFunded;
use App\Events\PaymentSucceeded;
use App\Models\Escrow;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class FundEscrowOnPaymentSucceeded
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(PaymentSucceeded $event): void
    {
        $payment = $event->payment;

        if ($payment->purpose->value !== 'investment') {
            return; // subscription payments are ActivateSubscriptionOnPayment's job
        }

        $escrow = $payment->payable;

        if (! $escrow instanceof Escrow) {
            return;
        }

        DB::transaction(function () use ($escrow, $payment) {
            // Row-locked re-read (spec §98) — protects against the
            // webhook somehow being processed twice concurrently (the
            // unique provider_event_id constraint already prevents this
            // at the PaymentTransaction level, but this is the Escrow
            // domain's own belt-and-suspenders, matching CreateEscrow's
            // "no business logic decided by the provider" principle
            // from spec §97).
            $locked = Escrow::whereKey($escrow->id)->lockForUpdate()->first();

            if ($locked->status !== EscrowStatus::Pending) {
                return; // already funded — a redelivered event is a no-op here too
            }

            // funded -> held happens together: there is no separate
            // manual "confirm funds are held" operational step in this
            // system, so both transitions are recorded (spec §34 lists
            // them as distinct states, both logged as separate
            // EscrowEvent rows) but happen atomically here.
            // Escrow::$fillable is deliberately empty — update() would
            // silently drop these.
            $locked->forceFill([
                'payment_id' => $payment->id,
                'status' => EscrowStatus::Held->value,
                'funded_at' => now(),
            ])->save();

            $locked->events()->create(['from_status' => EscrowStatus::Pending->value, 'to_status' => EscrowStatus::Funded->value]);
            $locked->events()->create(['from_status' => EscrowStatus::Funded->value, 'to_status' => EscrowStatus::Held->value]);

            $this->audit->log(null, 'escrow.funded', $locked, ['payment_id' => $payment->id]);

            EscrowFunded::dispatch($locked->fresh());
        });
    }
}
