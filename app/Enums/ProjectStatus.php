<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Published => 'Publié',
            self::Archived => 'Archivé',
            self::Closed => 'Clôturé',
        };
    }

    /** Statuses that count against the entrepreneur's active-project limit (spec §20). */
    public static function activeStatuses(): array
    {
        return [self::Draft, self::Published];
    }
}
