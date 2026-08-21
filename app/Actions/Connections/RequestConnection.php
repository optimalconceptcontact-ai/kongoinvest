<?php

namespace App\Actions\Connections;

use App\Enums\ConnectionStatus;
use App\Events\ConnectionRequested;
use App\Models\Connection;
use App\Models\Project;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RequestConnection
{
    public function handle(User $investor, Project $project, ?string $message = null): Connection
    {
        $investorProfile = $investor->investorProfile;

        $existing = Connection::where('project_id', $project->id)
            ->where('investor_profile_id', $investorProfile->id)
            ->whereIn('status', [ConnectionStatus::Pending->value, ConnectionStatus::Accepted->value])
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'connection' => 'Une demande de connexion est déjà en cours ou acceptée pour ce projet.',
            ]);
        }

        // project_id/investor_profile_id/requested_by_user_id/status are
        // deliberately outside Connection::$fillable — create() would
        // silently drop them, so forceCreate() bypasses that protection
        // for this trusted, server-controlled call.
        $connection = Connection::forceCreate([
            'project_id' => $project->id,
            'investor_profile_id' => $investorProfile->id,
            'requested_by_user_id' => $investor->id,
            'status' => ConnectionStatus::Pending->value,
            'message' => $message,
        ]);

        ConnectionRequested::dispatch($connection);

        return $connection;
    }
}
