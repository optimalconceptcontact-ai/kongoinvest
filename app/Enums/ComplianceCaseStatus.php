<?php

namespace App\Enums;

enum ComplianceCaseStatus: string
{
    case NotStarted = 'not_started';
    case CollectingInformation = 'collecting_information';
    case PendingDocuments = 'pending_documents';
    case AutomatedChecks = 'automated_checks';
    case ManualReview = 'manual_review';
    case EnhancedDueDiligence = 'enhanced_due_diligence';
    case Approved = 'approved';
    case ApprovedWithConditions = 'approved_with_conditions';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Suspended = 'suspended';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Non démarré',
            self::CollectingInformation => 'Collecte des informations',
            self::PendingDocuments => 'En attente de pièces',
            self::AutomatedChecks => 'Contrôles automatisés en cours',
            self::ManualReview => 'Examen manuel de conformité',
            self::EnhancedDueDiligence => 'Vigilance renforcée (EDD)',
            self::Approved => 'Approuvé',
            self::ApprovedWithConditions => 'Approuvé sous conditions',
            self::Rejected => 'Rejeté',
            self::Expired => 'Expiré',
            self::Suspended => 'Suspendu',
            self::Blocked => 'Bloqué',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Approved, self::ApprovedWithConditions, self::Rejected, self::Blocked], true);
    }

    public function allowsFinancialTransactions(): bool
    {
        return in_array($this, [self::Approved, self::ApprovedWithConditions], true);
    }
}
