<?php

namespace App\Notifications;

use App\Models\Connection;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Sent to the entrepreneur (project owner) when an investor requests a connection — spec §91. */
class ConnectionRequestNotification extends Notification
{
    use Queueable;

    // Named connectionModel, not connection — Queueable already declares
    // its own public $connection property (the queue connection name),
    // and a same-named promoted property fatals on trait composition.
    public function __construct(public Connection $connectionModel) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'connection_request',
            'connection_id' => $this->connectionModel->id,
            'investor_name' => $this->connectionModel->investorProfile->user->name,
            'project_title' => $this->connectionModel->project->title,
            'url' => '/entrepreneur/connections',
        ];
    }
}
