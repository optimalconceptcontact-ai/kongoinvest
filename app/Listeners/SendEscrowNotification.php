<?php

namespace App\Listeners;

use App\Events\EscrowDisputed;
use App\Events\EscrowFunded;
use App\Events\EscrowRefunded;
use App\Events\EscrowReleased;
use App\Models\Escrow;
use App\Notifications\EscrowNotification;

class SendEscrowNotification
{
    public function handleFunded(EscrowFunded $event): void
    {
        $this->notifyBoth($event->escrow, 'funded');
    }

    public function handleReleased(EscrowReleased $event): void
    {
        $this->notifyBoth($event->escrow, 'released');
    }

    public function handleRefunded(EscrowRefunded $event): void
    {
        $this->notifyBoth($event->escrow, 'refunded');
    }

    public function handleDisputed(EscrowDisputed $event): void
    {
        $this->notifyBoth($event->escrow, 'disputed');
    }

    private function notifyBoth(Escrow $escrow, string $event): void
    {
        $escrow->entrepreneur->notify(new EscrowNotification($escrow, $event));
        $escrow->investor->notify(new EscrowNotification($escrow, $event));
    }
}
