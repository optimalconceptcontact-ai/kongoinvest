<?php

namespace App\Listeners;

use App\Events\KycApproved;
use App\Events\KycRejected;
use App\Notifications\KycNotification;

class SendKycNotification
{
    public function handleApproved(KycApproved $event): void
    {
        $event->kycProfile->user->notify(new KycNotification($event->kycProfile, approved: true));
    }

    public function handleRejected(KycRejected $event): void
    {
        $event->kycProfile->user->notify(new KycNotification($event->kycProfile, approved: false));
    }
}
