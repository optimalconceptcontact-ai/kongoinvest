<?php

namespace App\Enums;

enum CheckStatus: string
{
    case Passed = 'passed';
    case Warning = 'warning';
    case Failed = 'failed';
    case NeedsReview = 'needs_review';

    public function label(): string
    {
        return match ($this) {
            self::Passed => 'Conforme',
            self::Warning => 'Avertissement',
            self::Failed => 'Échec',
            self::NeedsReview => 'Vérification humaine requise',
        };
    }
}
