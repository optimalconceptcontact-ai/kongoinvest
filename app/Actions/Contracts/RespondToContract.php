<?php

namespace App\Actions\Contracts;

use App\Enums\ContractStatus;
use App\Events\ContractAccepted;
use App\Models\Contract;
use App\Models\User;
use App\Services\AuditLogger;

class RespondToContract
{
    public function __construct(private AuditLogger $audit) {}

    public function accept(User $user, Contract $contract): Contract
    {
        $field = $user->id === $contract->dealRoom->entrepreneur_user_id
            ? 'entrepreneur_accepted_at'
            : 'investor_accepted_at';

        // entrepreneur_accepted_at/investor_accepted_at/status are
        // deliberately outside Contract::$fillable — update() would
        // silently drop them.
        $contract->forceFill([$field => now()])->save();
        $contract->refresh();

        if ($contract->isFullyAccepted()) {
            $contract->forceFill(['status' => ContractStatus::Accepted->value])->save();
        }

        // Contract acceptance is a named example in spec §49's audit
        // list — who accepted, on which side of the deal, and when.
        $this->audit->log($user, 'contract.accepted', $contract, [
            'fully_accepted' => $contract->isFullyAccepted(),
        ]);

        $contract = $contract->fresh();

        // ContractAccepted (notifying both parties) fires only once
        // BOTH signatures are in — a single side accepting is real
        // progress but not yet the event either party is waiting for.
        if ($contract->isFullyAccepted()) {
            ContractAccepted::dispatch($contract);
        }

        return $contract;
    }

    public function reject(User $user, Contract $contract): Contract
    {
        $contract->forceFill(['status' => ContractStatus::Rejected->value])->save();

        $this->audit->log($user, 'contract.rejected', $contract);

        return $contract->fresh();
    }
}
