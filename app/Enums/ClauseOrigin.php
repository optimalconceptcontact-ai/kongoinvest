<?php

namespace App\Enums;

/**
 * Origine d'une clause sélectionnée dans l'opération. Se combine
 * avec ClauseStatus (l'origine est conservée même après passage
 * en négociation, refus ou révision).
 */
enum ClauseOrigin: string
{
    case Ai = 'ai';
    case Founder = 'founder';
    case Investor = 'investor';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Ai => 'IA',
            self::Founder => 'Fondateur',
            self::Investor => 'Investisseur',
            self::Custom => 'Personnalisée',
        };
    }
}
