<?php

namespace App\Enums;

enum AiInteractionType: string
{
    case ProjectAnalysis = 'project_analysis'; // §50-52
    case EntrepreneurCoach = 'entrepreneur_coach'; // §53
    case InvestorAdvisor = 'investor_advisor'; // §54
    case ContractGeneration = 'contract_generation';
    case NegotiationAnalysis = 'negotiation_analysis'; // §13, double consentement (§14)

    public function label(): string
    {
        return match ($this) {
            self::ProjectAnalysis => 'Analyse de projet',
            self::EntrepreneurCoach => 'Coach IA entrepreneur',
            self::InvestorAdvisor => 'Coach IA investisseur',
            self::ContractGeneration => 'Génération de contrat par IA',
            self::NegotiationAnalysis => 'Analyse de négociation',
        };
    }
}
