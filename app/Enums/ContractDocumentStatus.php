<?php

namespace App\Enums;

/**
 * Cycle de vie d'un document contractuel généré. Un document
 * n'est jamais présenté comme un acte juridiquement valable
 * avant accomplissement des formalités requises (prompt §9).
 */
enum ContractDocumentStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Approved = 'approved';
    case PendingSignature = 'pending_signature';
    case Signed = 'signed';
    case Archived = 'archived';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Projet',
            self::InReview => 'En relecture',
            self::Approved => 'Approuvé',
            self::PendingSignature => 'En attente de signature',
            self::Signed => 'Signé',
            self::Archived => 'Archivé',
            self::Cancelled => 'Annulé',
        };
    }
}
