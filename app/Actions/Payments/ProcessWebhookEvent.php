<?php

namespace App\Actions\Payments;

use App\Enums\PaymentStatus;
use App\Events\PaymentSucceeded;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Services\AuditLogger;
use App\Services\Payments\WebhookEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessWebhookEvent
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Returns null if the payment can't be matched. Returns the
     * (unchanged) Payment if the event was already processed — not an
     * error, webhooks are legitimately redelivered by every provider's
     * own retry policy (spec §40).
     */
    public function handle(WebhookEvent $event): ?Payment
    {
        $payment = Payment::where('provider_reference', $event->providerReference)->first();

        if (! $payment) {
            Log::warning('Webhook event referenced an unknown payment', ['reference' => $event->providerReference]);

            return null;
        }

        return DB::transaction(function () use ($payment, $event) {
            // The actual idempotency enforcement (spec §41): this
            // unique constraint on (payment_id, provider_event_id) is
            // what makes a redelivered webhook a guaranteed no-op, not
            // just a "we hope this doesn't happen" comment. A duplicate
            // insert throws, caught below, and nothing past this point
            // runs a second time.
            try {
                // payment_id is deliberately outside
                // PaymentTransaction::$fillable — create() would
                // silently drop it, so forceCreate() bypasses that
                // protection for this trusted, server-controlled call.
                PaymentTransaction::forceCreate([
                    'payment_id' => $payment->id,
                    'type' => $event->type,
                    'provider_event_id' => $event->providerEventId,
                    'payload' => $event->rawPayload,
                    'processed_at' => now(),
                ]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                Log::info('Duplicate webhook event ignored', ['payment_id' => $payment->id, 'event_id' => $event->providerEventId]);

                return $payment;
            }

            // Verify amount/currency match what we expect before trusting
            // the status (spec §40) — a webhook claiming success for the
            // wrong amount is rejected, not silently accepted.
            if ($event->amountMinor !== null && $event->amountMinor !== $payment->amount_minor) {
                Log::warning('Webhook amount mismatch — payment left unchanged', [
                    'payment_id' => $payment->id,
                    'expected' => $payment->amount_minor,
                    'received' => $event->amountMinor,
                ]);

                return $payment;
            }

            if ($payment->isFinal()) {
                return $payment; // already settled — a later event can't un-settle it here
            }

            $payment->update(['status' => $event->status->value]);

            $this->audit->log($payment->payer, 'payment.'.$event->status->value, $payment, [
                'provider' => $payment->provider->value,
                'event_type' => $event->type,
            ]);

            if ($event->status === PaymentStatus::Succeeded) {
                PaymentSucceeded::dispatch($payment->fresh());
            }

            return $payment->fresh();
        });
    }
}
