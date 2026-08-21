<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Processing => 'En cours',
            self::Succeeded => 'Réussi',
            self::Failed => 'Échoué',
            self::Refunded => 'Remboursé',
            self::PartiallyRefunded => 'Partiellement remboursé',
            self::Cancelled => 'Annulé',
        };
    }
}
