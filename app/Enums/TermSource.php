<?php

namespace App\Enums;

enum TermSource: string
{
    case Conversation = 'conversation';
    case Document = 'document';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Conversation => 'Conversation',
            self::Document => 'Document',
            self::Manual => 'Saisie manuelle',
        };
    }
}
