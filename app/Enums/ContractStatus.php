<?php

namespace App\Enums;

enum ContractStatus: string
{
    case Draft = 'draft';
    case Generated = 'generated';
    case Sent = 'sent';
    case Viewed = 'viewed';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Generated => 'Généré',
            self::Sent => 'Envoyé',
            self::Viewed => 'Consulté',
            self::Accepted => 'Accepté',
            self::Rejected => 'Refusé',
            self::Expired => 'Expiré',
            self::Cancelled => 'Annulé',
        };
    }
}
