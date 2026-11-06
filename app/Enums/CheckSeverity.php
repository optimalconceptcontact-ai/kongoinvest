<?php

namespace App\Enums;

enum CheckSeverity: string
{
    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public function label(): string
    {
        return match ($this) {
            self::Critical => 'Critique',
            self::High => 'Élevée',
            self::Medium => 'Moyenne',
            self::Low => 'Faible',
        };
    }

    /**
     * Seules les anomalies critiques empêchent la finalisation
     * (prompt §10). Le moteur de clôture s'appuie sur ce filtre.
     */
    public function blocksClosing(): bool
    {
        return $this === self::Critical;
    }
}
