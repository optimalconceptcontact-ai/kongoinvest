<?php

namespace App\Enums;

/**
 * La signature est représentée par la preuve du prestataire ou
 * du dépôt des exemplaires signés — elle n'est jamais simulée
 * par une validation d'interface (prompt §11).
 */
enum SignatureStatus: string
{
    case Requested = 'requested';
    case Completed = 'completed';
    case Refused = 'refused';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Demandée',
            self::Completed => 'Signée',
            self::Refused => 'Refusée',
            self::Void => 'Annulée',
        };
    }
}
