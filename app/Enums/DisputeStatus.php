<?php

namespace App\Enums;

enum DisputeStatus: string
{
    case Opened = 'opened';
    case UnderReview = 'under_review';
    case Resolved = 'resolved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Opened => 'Ouvert',
            self::UnderReview => 'En cours de révision',
            self::Resolved => 'Résolu',
            self::Rejected => 'Rejeté',
            self::Cancelled => 'Annulé',
        };
    }
}
