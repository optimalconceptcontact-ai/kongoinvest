<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Spec §116-ish (payment events). A listener here is what will actually
 * switch a pending subscription plan change to active once billing is
 * fully wired end-to-end, and later (Phase 11) what funds an Escrow —
 * neither listener exists yet, same "fire the event now, attach the
 * reaction later" pattern as MatchCreated/ConnectionAccepted.
 */
class PaymentSucceeded
{
    use Dispatchable, SerializesModels;

    public function __construct(public Payment $payment) {}
}
