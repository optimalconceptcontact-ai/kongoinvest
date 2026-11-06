<?php

namespace App\Enums;

/**
 * Décision d'une partie sur une clause. La contre-proposition
 * porte son propre texte ; l'acceptation et le refus sont
 * historisés et non modifiables.
 */
enum ClauseDecision: string
{
    case Accept = 'accept';
    case Refuse = 'refuse';
    case CounterProposal = 'counter_proposal';
    case RequestRevision = 'request_revision';

    public function label(): string
    {
        return match ($this) {
            self::Accept => 'Acceptée',
            self::Refuse => 'Refusée',
            self::CounterProposal => 'Contre-proposition',
            self::RequestRevision => 'Révision demandée',
        };
    }
}
