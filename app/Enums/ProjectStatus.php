<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Rejected = 'rejected';
    case Archived = 'archived';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::PendingReview => 'En attente de validation',
            self::Published => 'Publié',
            self::Rejected => 'Rejeté',
            self::Archived => 'Archivé',
            self::Closed => 'Clôturé',
        };
    }

    /** Statuses that count against the entrepreneur's active-project limit (spec §20). */
    public static function activeStatuses(): array
    {
        return [self::Draft, self::PendingReview, self::Rejected, self::Published];
    }
}
