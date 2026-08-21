<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use App\Notifications\PaymentNotification;

class SendPaymentNotification
{
    public function handle(PaymentSucceeded $event): void
    {
        $event->payment->payer->notify(new PaymentNotification($event->payment));
    }
}
