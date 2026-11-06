<?php

namespace App\Enums;

/**
 * Niveau de vérification des données juridiques de l'entreprise
 * (forme, capital, associés). Une donnée non vérifiée ne peut
 * jamais alimenter une clause sans générer une alerte.
 */
enum DataVerificationStatus: string
{
    case Unverified = 'unverified';
    case Partial = 'partial';
    case Verified = 'verified';

    public function label(): string
    {
        return match ($this) {
            self::Unverified => 'Non vérifiée',
            self::Partial => 'Partiellement vérifiée',
            self::Verified => 'Vérifiée',
        };
    }
}
