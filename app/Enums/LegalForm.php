<?php

namespace App\Enums;

/**
 * Formes juridiques OHADA prises en charge. La structure de
 * détention du capital (unipersonnelle / pluripersonnelle) n'est
 * PAS stockée ici : SARLU = SARL à associé unique est déduite de
 * la structure des associés réellement enregistrés
 * (company_shareholdings) et non d'un cas d'enum distinct.
 */
enum LegalForm: string
{
    case Sarlu = 'sarlu';
    case Sarl = 'sarl';
    case Sa = 'sa';
    case Sas = 'sas';
    case Sasu = 'sasu';
    case Other = 'other';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Sarlu => 'SARLU',
            self::Sarl => 'SARL',
            self::Sa => 'S.A.',
            self::Sas => 'SAS',
            self::Sasu => 'SASU',
            self::Other => 'Autre forme',
            self::Unknown => 'Non renseignée',
        };
    }

    /**
     * Forme de base (SARL pour SARLU, SAS pour SASU). Les règles
     * AUSCGIE applicables dépendent de la forme de base ; le
     * caractère unipersonnel est une donnée de structure du capital.
     */
    public function baseForm(): ?self
    {
        return match ($this) {
            self::Sarlu => self::Sarl,
            self::Sasu => self::Sas,
            self::Sarl, self::Sa, self::Sas => $this,
            self::Other, self::Unknown => null,
        };
    }

    public function isSarlFamily(): bool
    {
        return in_array($this, [self::Sarlu, self::Sarl], true);
    }

    public function isSasFamily(): bool
    {
        return in_array($this, [self::Sasu, self::Sas], true);
    }

    public function isSingleMemberForm(): bool
    {
        return in_array($this, [self::Sarlu, self::Sasu], true);
    }
}
