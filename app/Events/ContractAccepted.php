<?php

namespace App\Events;

use App\Models\Contract;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Dispatched only once BOTH parties have accepted — see RespondToContract::accept(). */
class ContractAccepted
{
    use Dispatchable, SerializesModels;

    public function __construct(public Contract $contract) {}
}
