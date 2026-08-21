<?php

namespace App\Actions\Connections;

use App\Enums\ConnectionStatus;
use App\Models\Connection;

class CancelConnection
{
    public function handle(Connection $connection): Connection
    {
        // status/responded_at are deliberately outside Connection::$fillable
        // — update() would silently drop them.
        $connection->forceFill([
            'status' => ConnectionStatus::Cancelled->value,
            'responded_at' => now(),
        ])->save();

        return $connection->fresh();
    }
}
