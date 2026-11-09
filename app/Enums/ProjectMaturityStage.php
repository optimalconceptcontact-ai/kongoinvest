<?php

namespace App\Enums;

enum ProjectMaturityStage: string
{
    case Idea = 'idea';
    case Validation = 'validation';
    case Prototype = 'prototype';
    case Mvp = 'mvp';
    case EarlyStage = 'early_stage';
    case Growth = 'growth';
    case Scale = 'scale';

    public function label(): string
    {
        return match ($this) {
            self::Idea => 'Idée (non encore validée)',
            self::Validation => 'Validation (étude de marché et concept)',
            self::Prototype => 'Prototype / Preuve de concept (PoC)',
            self::Mvp => 'Produit Minimum Viable (MVP)',
            self::EarlyStage => 'Amorçage (début d\'activité commerciale)',
            self::Growth => 'Croissance (traction établie)',
            self::Scale => 'Expansion / Échelle',
        };
    }

    public function requiresIncorporation(): bool
    {
        // Seules les phases de croissance et d'échelle requièrent obligatoirement une société constituée pour des financements en capital
        return in_array($this, [self::Growth, self::Scale], true);
    }
}
