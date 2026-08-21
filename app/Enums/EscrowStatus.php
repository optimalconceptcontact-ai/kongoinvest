<?php

namespace App\Enums;

enum EscrowStatus: string
{
    case Pending = 'pending'; // created, awaiting funding
    case Funded = 'funded'; // payment captured
    case Held = 'held'; // funds confirmed held — the stable "waiting for investor decision" state
    case AwaitingRelease = 'awaiting_release'; // investor confirmed release, payout in progress
    case Released = 'released';
    case RefundRequested = 'refund_requested'; // investor requested refund, provider refund in progress
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';
    case Disputed = 'disputed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente de financement',
            self::Funded => 'Financé',
            self::Held => 'Retenu en séquestre',
            self::AwaitingRelease => 'Libération en cours',
            self::Released => 'Libéré',
            self::RefundRequested => 'Remboursement demandé',
            self::Refunded => 'Remboursé',
            self::Cancelled => 'Annulé',
            self::Disputed => 'En litige',
        };
    }

    /** States from which a dispute can be raised (spec §129). */
    public static function disputable(): array
    {
        return [self::Held, self::AwaitingRelease, self::RefundRequested];
    }
}
