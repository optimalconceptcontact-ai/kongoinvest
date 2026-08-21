<?php

namespace App\Listeners;

use App\Events\ContractAccepted;
use App\Notifications\ContractNotification;

class SendContractNotification
{
    public function handle(ContractAccepted $event): void
    {
        $event->contract->dealRoom->entrepreneur->notify(new ContractNotification($event->contract));
        $event->contract->dealRoom->investor->notify(new ContractNotification($event->contract));
    }
}
