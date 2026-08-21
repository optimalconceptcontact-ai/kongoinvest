<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ContractNotification extends Notification
{
    use Queueable;

    public function __construct(public Contract $contract) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'contract_accepted',
            'contract_id' => $this->contract->id,
            'project_title' => $this->contract->dealRoom->project->title,
            'url' => "/deal-rooms/{$this->contract->deal_room_id}",
        ];
    }
}
