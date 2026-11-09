<?php

namespace App\Enums;

enum AmlRiskLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Risque faible',
            self::Medium => 'Risque standard / moyen',
            self::High => 'Risque élevé (Vigilance renforcée)',
            self::Critical => 'Risque critique (Blocage / Examen CO)',
        };
    }

    public function requiresEnhancedDueDiligence(): bool
    {
        return in_array($this, [self::High, self::Critical], true);
    }
}
