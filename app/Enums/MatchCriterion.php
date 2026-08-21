<?php

namespace App\Enums;

/**
 * The 8 weighted criteria from spec §23. Split by scoring source: the
 * first five are objective/structured data the Rules Engine can score
 * directly; the last three (business model, traction, impact) are
 * free-text narrative that genuinely needs semantic judgment, so they go
 * through AiSemanticAnalyzer when available and fall back to a neutral
 * completeness heuristic when it isn't (spec §113 — AI failure must
 * never break the page).
 */
enum MatchCriterion: string
{
    case Sector = 'sector';
    case Stage = 'stage';
    case Funding = 'funding';
    case Geography = 'geography';
    case Risk = 'risk';
    case BusinessModel = 'business';
    case Traction = 'traction';
    case Impact = 'impact';

    public function label(): string
    {
        return match ($this) {
            self::Sector => 'Secteur',
            self::Stage => 'Stade',
            self::Funding => 'Financement',
            self::Geography => 'Géographie',
            self::Risk => 'Risque',
            self::BusinessModel => 'Modèle économique',
            self::Traction => 'Traction',
            self::Impact => 'Impact',
        };
    }

    public function isAiScored(): bool
    {
        return in_array($this, [self::BusinessModel, self::Traction, self::Impact], true);
    }

    /** @return self[] */
    public static function ruleScored(): array
    {
        return [self::Sector, self::Stage, self::Funding, self::Geography, self::Risk];
    }

    /** @return self[] */
    public static function aiScored(): array
    {
        return [self::BusinessModel, self::Traction, self::Impact];
    }
}
