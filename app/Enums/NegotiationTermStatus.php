<?php

namespace App\Enums;

/**
 * Classification d'une information extraite des négociations
 * (prompt §4). Une simple discussion n'est jamais un accord :
 * la progression intention → proposition → négociation → accord
 * de principe → acceptation explicite → engagement formalisé
 * est explicite et traçable.
 */
enum NegotiationTermStatus: string
{
    case InitialProposal = 'initial_proposal';
    case CounterProposal = 'counter_proposal';
    case ExplicitlyAccepted = 'explicitly_accepted';
    case ExplicitlyRefused = 'explicitly_refused';
    case UnderDiscussion = 'under_discussion';
    case ToClarify = 'to_clarify';
    case Contradictory = 'contradictory';
    case ToConfirmByDocument = 'to_confirm_by_document';
    case Formalized = 'formalized';

    public function label(): string
    {
        return match ($this) {
            self::InitialProposal => 'Proposition initiale',
            self::CounterProposal => 'Contre-proposition',
            self::ExplicitlyAccepted => 'Acceptée explicitement',
            self::ExplicitlyRefused => 'Refusée explicitement',
            self::UnderDiscussion => 'En discussion',
            self::ToClarify => 'À clarifier',
            self::Contradictory => 'Contradictoire',
            self::ToConfirmByDocument => 'À confirmer par un document',
            self::Formalized => 'Engagement formalisé',
        };
    }

    /**
     * Statuts qui ne peuvent JAMAIS être traités comme un accord
     * définitif par le moteur contractuel (prompt §4, règles).
     */
    public function isBindingLike(): bool
    {
        return in_array($this, [
            self::ExplicitlyAccepted,
            self::Formalized,
        ], true);
    }
}
