<?php

namespace App\Enums;

enum AiInteractionType: string
{
    case ProjectAnalysis = 'project_analysis'; // §50-52
    case EntrepreneurCoach = 'entrepreneur_coach'; // §53
    case InvestorAdvisor = 'investor_advisor'; // §54
    case ContractGeneration = 'contract_generation';

    public function label(): string
    {
        return match ($this) {
            self::ProjectAnalysis => 'Analyse de projet',
            self::EntrepreneurCoach => "Coach IA entrepreneur",
            self::InvestorAdvisor => 'Conseiller IA investisseur',
            self::ContractGeneration => 'Génération de contrat par IA',
        };
    }
}
