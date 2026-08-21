<?php

namespace App\Events;

use App\Models\Connection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Spec §116. No listener yet — Phase 13 attaches ConnectionRequestNotification. */
class ConnectionRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(public Connection $connection) {}
}
