<?php

namespace App\Actions\Escrow;

use App\Enums\DisputeStatus;
use App\Enums\EscrowStatus;
use App\Models\Dispute;
use App\Models\Escrow;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class ResolveDispute
{
    public function __construct(
        private AuditLogger $audit,
        private ReleaseEscrow $release,
        private RefundEscrow $refund,
    ) {}

    /**
     * @param  'release'|'refund'|'reject'  $resolution
     */
    public function handle(User $admin, Dispute $dispute, string $resolution, string $notes): Dispute
    {
        // Deliberately NOT one big transaction wrapping the whole method:
        // 'refund' calls RefundEscrow, which makes a real HTTP call to
        // the payment provider. Holding a row lock (and an open DB
        // transaction) across a network call is exactly the kind of
        // thing spec §98's "row locking lorsque nécessaire" should
        // prevent, not cause — each step below commits its own short
        // transaction instead.
        DB::transaction(function () use ($admin, $dispute, $resolution, $notes) {
            // Dispute::$fillable is only ['reason'] — update() would
            // silently drop these.
            $dispute->forceFill([
                'status' => $resolution === 'reject' ? DisputeStatus::Rejected->value : DisputeStatus::Resolved->value,
                'resolution_notes' => $notes,
                'resolved_by_user_id' => $admin->id,
                'resolved_at' => now(),
            ])->save();

            $this->returnToHeld($admin, $dispute->escrow()->lockForUpdate()->first());
        });

        $escrow = $dispute->escrow->fresh();

        match ($resolution) {
            'release' => $this->release->handle($escrow->investor, $escrow),
            'refund' => $this->refund->handle($escrow->investor, $escrow),
            default => null, // 'reject' — already returned to Held above, nothing further to do
        };

        $this->audit->log($admin, 'dispute.'.($resolution === 'reject' ? 'rejected' : 'resolved'), $dispute, [
            'resolution' => $resolution,
            'escrow_id' => $escrow->id,
        ]);

        return $dispute->fresh();
    }

    private function returnToHeld(User $admin, Escrow $escrow): void
    {
        // Escrow::$fillable is deliberately empty — update() would
        // silently drop this.
        $escrow->forceFill(['status' => EscrowStatus::Held->value])->save();
        $escrow->events()->create(['from_status' => EscrowStatus::Disputed->value, 'to_status' => EscrowStatus::Held->value, 'actor_id' => $admin->id]);
    }
}
