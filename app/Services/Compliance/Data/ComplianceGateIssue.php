<?php

namespace App\Services\Compliance\Data;

use App\Enums\CheckSeverity;

/**
 * Un motif de blocage ou d'avertissement produit par le
 * InvestmentComplianceGateService. Un code stable permet aux
 * appelants et aux tests de raisonner sans dépendre du libellé.
 */
final readonly class ComplianceGateIssue
{
    public function __construct(
        public string $code,
        public string $message,
        public CheckSeverity $severity = CheckSeverity::Critical,
        public ?string $subject = null,
    ) {}

    public function blocks(): bool
    {
        return $this->severity->blocksClosing();
    }

    /**
     * @return array{code: string, message: string, severity: string, subject: string|null}
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'severity' => $this->severity->value,
            'subject' => $this->subject,
        ];
    }
}
