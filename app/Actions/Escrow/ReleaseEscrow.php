<?php

namespace App\Actions\Escrow;

use App\Enums\EscrowStatus;
use App\Events\EscrowReleased;
use App\Models\Escrow;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReleaseEscrow
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Spec §147 is explicit: KongoInvest is not itself a financial
     * institution, and must respect each payment provider's own rules
     * for moving money to a beneficiary. Our PaymentProviderInterface
     * (spec §96) deliberately has no "payout" method — it only defines
     * createPayment/verifyPayment/refund/getStatus/handleWebhook. For a
     * Stripe Connect–funded escrow, the funds already moved toward the
     * entrepreneur's connected account at funding time via
     * `transfer_data.destination` (see StripeConnectProvider); "release"
     * here is KongoInvest's own authorization record confirming the investor
     * is satisfied, not a second money-movement API call. For every
     * other rail (plain Stripe, PayPal, Mobile Money), actually paying
     * the entrepreneur is a real operational step — a bank transfer, a
     * Mobile Money payout — that happens outside this interface and
     * this scaffold's scope. This method records the authorization
     * correctly; it does not pretend to move money it structurally
     * cannot move through the current provider contract.
     */
    public function handle(User $investor, Escrow $escrow, ?string $payoutReference = null): Escrow
    {
        return DB::transaction(function () use ($investor, $escrow, $payoutReference) {
            // Row lock (spec §98) — the actual protection against a
            // double-click or two concurrent requests both trying to
            // release the same escrow. The second one to acquire the
            // lock sees status already changed and is rejected below,
            // never processes the release twice.
            $locked = Escrow::whereKey($escrow->id)->lockForUpdate()->first();

            if ($locked->status !== EscrowStatus::Held) {
                throw ValidationException::withMessages([
                    'escrow' => 'Ce séquestre ne peut pas être libéré dans son état actuel.',
                ]);
            }

            // Escrow::$fillable is deliberately empty — update() would
            // silently drop these.
            $locked->forceFill([
                'status' => EscrowStatus::Released->value,
                'payout_reference' => $payoutReference,
                'released_at' => now(),
            ])->save();

            $locked->events()->create([
                'from_status' => EscrowStatus::Held->value,
                'to_status' => EscrowStatus::Released->value,
                'actor_id' => $investor->id,
            ]);

            // Named explicitly in spec §49's audit list.
            $this->audit->log($investor, 'escrow.released', $locked, ['payout_reference' => $payoutReference]);

            $locked = $locked->fresh();
            EscrowReleased::dispatch($locked);

            return $locked;
        });
    }
}
