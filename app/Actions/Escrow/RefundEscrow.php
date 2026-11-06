<?php

namespace App\Actions\Escrow;

use App\Enums\EscrowStatus;
use App\Events\EscrowRefunded;
use App\Models\Escrow;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Payments\PaymentProviderFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundEscrow
{
    public function __construct(
        private PaymentProviderFactory $providers,
        private AuditLogger $audit,
    ) {}

    /**
     * Unlike release(), a refund genuinely IS one of
     * PaymentProviderInterface's four methods — the original charge is
     * simply reversed through the same rail it came in on, which every
     * provider's own API supports directly. No boundary-crossing
     * problem here the way release() has.
     */
    public function handle(User $investor, Escrow $escrow): Escrow
    {
        $locked = DB::transaction(function () use ($escrow) {
            $locked = Escrow::whereKey($escrow->id)->lockForUpdate()->first();

            if ($locked->status !== EscrowStatus::Held) {
                throw ValidationException::withMessages([
                    'escrow' => 'Ce séquestre ne peut pas être remboursé dans son état actuel.',
                ]);
            }

            // Escrow::$fillable is deliberately empty — update() would
            // silently drop this.
            $locked->forceFill(['status' => EscrowStatus::RefundRequested->value])->save();
            $locked->events()->create([
                'from_status' => EscrowStatus::Held->value,
                'to_status' => EscrowStatus::RefundRequested->value,
                'actor_id' => $locked->investor_user_id,
            ]);

            return $locked;
        });

        $payment = $locked->fundingPayment;
        $provider = $this->providers->make($payment->provider);
        $result = $provider->refund($payment);

        return DB::transaction(function () use ($investor, $locked, $result) {
            $current = Escrow::whereKey($locked->id)->lockForUpdate()->first();

            if (! $result->successful) {
                // Provider refund failed — stay in refund_requested rather
                // than silently reverting to Held, so this is visibly
                // "needs attention" (an admin can retry or investigate),
                // never a state that looks like nothing happened.
                $this->audit->log($investor, 'escrow.refund_failed', $current, ['reason' => $result->failureReason]);

                throw ValidationException::withMessages([
                    'escrow' => 'Le remboursement a échoué auprès du fournisseur de paiement: '.$result->failureReason,
                ]);
            }

            if ($current->status !== EscrowStatus::RefundRequested) {
                // The escrow left refund_requested while the provider call
                // was in flight (typically a dispute was opened, which
                // refund_requested explicitly allows). Never overwrite that
                // newer state: the provider-side refund already happened,
                // so leave the current state for an admin to reconcile.
                $this->audit->log($investor, 'escrow.refund_superseded', $current, [
                    'provider_refund_id' => $result->providerRefundId,
                    'current_status' => $current->status->value,
                ]);

                throw ValidationException::withMessages([
                    'escrow' => 'Ce séquestre a changé d\'état pendant le remboursement (litige ouvert). Le remboursement a été enregistré côté fournisseur et un administrateur va réconcilier le dossier.',
                ]);
            }

            $current->forceFill([
                'status' => EscrowStatus::Refunded->value,
                'refund_reference' => $result->providerRefundId,
                'refunded_at' => now(),
            ])->save();

            $current->events()->create([
                'from_status' => EscrowStatus::RefundRequested->value,
                'to_status' => EscrowStatus::Refunded->value,
                'actor_id' => $investor->id,
            ]);

            $this->audit->log($investor, 'escrow.refunded', $current, ['provider_refund_id' => $result->providerRefundId]);

            $current = $current->fresh();
            EscrowRefunded::dispatch($current);

            return $current;
        });
    }
}
