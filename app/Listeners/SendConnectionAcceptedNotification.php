<?php

namespace App\Listeners;

use App\Events\ConnectionAccepted;
use App\Notifications\ConnectionAcceptedNotification;

class SendConnectionAcceptedNotification
{
    public function handle(ConnectionAccepted $event): void
    {
        $event->connection->investorProfile->user->notify(new ConnectionAcceptedNotification($event->connection));
    }
}
