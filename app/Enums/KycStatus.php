<?php

namespace App\Enums;

enum KycStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Submitted => 'Soumis',
            self::UnderReview => 'En cours de vérification',
            self::Approved => 'Approuvé',
            self::Rejected => 'Rejeté',
            self::Expired => 'Expiré',
        };
    }
}
