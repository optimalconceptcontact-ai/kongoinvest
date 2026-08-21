<?php

namespace App\Listeners;

use App\Events\ConnectionRequested;
use App\Notifications\ConnectionRequestNotification;

class SendConnectionRequestNotification
{
    public function handle(ConnectionRequested $event): void
    {
        $event->connection->project->user->notify(new ConnectionRequestNotification($event->connection));
    }
}
