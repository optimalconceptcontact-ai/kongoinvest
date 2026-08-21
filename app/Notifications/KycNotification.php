<?php

namespace App\Notifications;

use App\Models\KycProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class KycNotification extends Notification
{
    use Queueable;

    public function __construct(public KycProfile $kycProfile, public bool $approved) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => $this->approved ? 'kyc_approved' : 'kyc_rejected',
            'rejection_reason' => $this->approved ? null : $this->kycProfile->rejection_reason,
            'url' => '/settings/kyc',
        ];
    }
}
