<?php

namespace App\Actions\Escrow;

use App\Enums\DisputeStatus;
use App\Enums\EscrowStatus;
use App\Events\EscrowDisputed;
use App\Models\Dispute;
use App\Models\Escrow;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RaiseDispute
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(User $raiser, Escrow $escrow, string $reason): Dispute
    {
        return DB::transaction(function () use ($raiser, $escrow, $reason) {
            $locked = Escrow::whereKey($escrow->id)->lockForUpdate()->first();

            if (! in_array($locked->status, EscrowStatus::disputable(), true)) {
                throw ValidationException::withMessages([
                    'escrow' => "Ce séquestre n'est pas dans un état permettant d'ouvrir un litige.",
                ]);
            }

            if ($locked->openDispute()) {
                throw ValidationException::withMessages([
                    'escrow' => 'Un litige est déjà ouvert pour ce séquestre.',
                ]);
            }

            $previousStatus = $locked->status;

            // Escrow::$fillable is deliberately empty — update() would
            // silently drop this.
            $locked->forceFill(['status' => EscrowStatus::Disputed->value])->save();
            $locked->events()->create([
                'from_status' => $previousStatus->value,
                'to_status' => EscrowStatus::Disputed->value,
                'actor_id' => $raiser->id,
                'metadata' => ['reason' => $reason],
            ]);

            // Dispute::$fillable is only ['reason'] — create() would
            // silently drop raised_by_user_id/status.
            $dispute = $locked->disputes()->forceCreate([
                'raised_by_user_id' => $raiser->id,
                'status' => DisputeStatus::Opened->value,
                'reason' => $reason,
            ]);

            $this->audit->log($raiser, 'dispute.opened', $dispute, ['escrow_id' => $locked->id]);

            EscrowDisputed::dispatch($locked->fresh());

            return $dispute;
        });
    }
}
