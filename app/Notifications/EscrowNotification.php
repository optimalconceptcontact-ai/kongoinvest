<?php

namespace App\Notifications;

use App\Models\Escrow;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * One class covering funded/released/refunded/disputed — spec §91 lists
 * "EscrowNotification" singular, and all four events share the same
 * shape (which Escrow, which deal). $event distinguishes them for the
 * frontend's copy/icon.
 */
class EscrowNotification extends Notification
{
    use Queueable;

    public function __construct(public Escrow $escrow, public string $event) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'escrow_'.$this->event,
            'escrow_id' => $this->escrow->id,
            'project_title' => $this->escrow->dealRoom->project->title,
            'amount_minor' => $this->escrow->amount_minor,
            'currency' => $this->escrow->currency,
            'url' => "/deal-rooms/{$this->escrow->deal_room_id}",
        ];
    }
}
