<?php

namespace App\Enums;

enum WorkspaceStatus: string
{
    case Draft = 'draft';
    case Setup = 'setup';
    case Negotiation = 'negotiation';
    case ClauseSelection = 'clause_selection';
    case Documentation = 'documentation';
    case Review = 'review';
    case Approval = 'approval';
    case Signature = 'signature';
    case Closing = 'closing';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Ébauche',
            self::Setup => 'Configuration',
            self::Negotiation => 'Négociation',
            self::ClauseSelection => 'Sélection des clauses',
            self::Documentation => 'Documentation',
            self::Review => 'Vérifications',
            self::Approval => 'Validation',
            self::Signature => 'Signature',
            self::Closing => 'Clôture',
            self::Closed => 'Clôturée',
            self::Cancelled => 'Annulée',
        };
    }
}
