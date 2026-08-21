<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Covers both PaymentPurpose cases (subscription and investment) with
 * one class — the `data` payload's `purpose` field is what the frontend
 * uses to render the right copy, rather than two near-identical classes.
 */
class PaymentNotification extends Notification
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'payment_succeeded',
            'payment_id' => $this->payment->id,
            'purpose' => $this->payment->purpose->value,
            'amount_minor' => $this->payment->amount_minor,
            'currency' => $this->payment->currency,
            'url' => $this->payment->purpose->value === 'subscription' ? '/settings/subscription' : '/dashboard',
        ];
    }
}
