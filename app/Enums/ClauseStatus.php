<?php

namespace App\Enums;

/**
 * Statut de négociation d'une clause dans un espace contractuel
 * (prompt §8). Une clause n'est définitivement convenue que
 * lorsqu'une acceptation des deux parties est enregistrée —
 * l'acceptation explicite est matérialisée par des enregistrements
 * contract_clause_approvals, jamais par un simple statut.
 */
enum ClauseStatus: string
{
    case NotProposed = 'not_proposed';
    case ProposedByAi = 'proposed_by_ai';
    case ProposedByFounder = 'proposed_by_founder';
    case ProposedByInvestor = 'proposed_by_investor';
    case InNegotiation = 'in_negotiation';
    case AcceptedByBoth = 'accepted_by_both';
    case Refused = 'refused';
    case ToRevise = 'to_revise';
    case PendingLegalValidation = 'pending_legal_validation';

    public function label(): string
    {
        return match ($this) {
            self::NotProposed => 'Non proposée',
            self::ProposedByAi => 'Proposée par l’IA',
            self::ProposedByFounder => 'Proposée par le fondateur',
            self::ProposedByInvestor => 'Proposée par l’investisseur',
            self::InNegotiation => 'En négociation',
            self::AcceptedByBoth => 'Acceptée par les deux parties',
            self::Refused => 'Refusée',
            self::ToRevise => 'À réviser',
            self::PendingLegalValidation => 'En attente de validation juridique',
        };
    }
}
