<?php

namespace App\Actions\Connections;

use App\Enums\ConnectionStatus;
use App\Events\ConnectionAccepted;
use App\Models\Connection;
use App\Models\Conversation;
use App\Models\DealRoom;
use Illuminate\Support\Facades\DB;

class RespondToConnection
{
    public function accept(Connection $connection): DealRoom
    {
        return DB::transaction(function () use ($connection) {
            // Every field here is deliberately outside its model's
            // $fillable (Connection, DealRoom, and Conversation all
            // guard against request-driven writes) — update()/create()
            // would silently drop them, so force* bypasses that
            // protection for this trusted, server-controlled call.
            $connection->forceFill([
                'status' => ConnectionStatus::Accepted->value,
                'responded_at' => now(),
            ])->save();

            $dealRoom = DealRoom::forceCreate([
                'connection_id' => $connection->id,
                'project_id' => $connection->project_id,
                'entrepreneur_user_id' => $connection->project->user_id,
                'investor_user_id' => $connection->investorProfile->user_id,
                'status' => 'active',
            ]);

            Conversation::forceCreate(['deal_room_id' => $dealRoom->id]);

            ConnectionAccepted::dispatch($connection->fresh());

            return $dealRoom;
        });
    }

    public function reject(Connection $connection): Connection
    {
        $connection->forceFill([
            'status' => ConnectionStatus::Rejected->value,
            'responded_at' => now(),
        ])->save();

        return $connection->fresh();
    }
}
