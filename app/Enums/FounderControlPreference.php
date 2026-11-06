<?php

namespace App\Enums;

/**
 * Préférence du fondateur quant au contrôle de son entreprise.
 * Elle influence les clauses recommandées mais n'impose rien :
 * les parties restent libres de négocier toute autre configuration
 * (prompt §3.C).
 */
enum FounderControlPreference: string
{
    case ControlRetained = 'control_retained';
    case SharedGovernance = 'shared_governance';
    case InvestorParticipation = 'investor_participation';
    case Flexible = 'flexible';
    case OpenNegotiation = 'open_negotiation';

    public function label(): string
    {
        return match ($this) {
            self::ControlRetained => 'Contrôle conservé',
            self::SharedGovernance => 'Gouvernance partagée',
            self::InvestorParticipation => 'Participation active de l’investisseur',
            self::Flexible => 'Position flexible',
            self::OpenNegotiation => 'Négociation ouverte',
        };
    }
}
