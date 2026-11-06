<?php

namespace App\Enums;

enum SignatureMethod: string
{
    case ElectronicProvider = 'electronic_provider';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::ElectronicProvider => 'Signature électronique (prestataire)',
            self::External => 'Signature externe (dépôt des exemplaires signés)',
        };
    }
}
