<?php

namespace App\Services\Contracts\Data;

use App\Enums\CheckSeverity;
use App\Enums\CheckStatus;
use App\Enums\LegalCheckType;

/**
 * Contrôle juridique brouillon bâti par le
 * LegalComplianceService : un check détient ses contraintes
 * typées jusqu'à la persistance — jamais un tableau à clés
 * réinventé à chaque appel.
 */
final readonly class LegalCheckDraft
{
    /**
     * @param  array<string, mixed>  $details
     * @param  array<string, mixed>  $references
     */
    public function __construct(
        public LegalCheckType $type,
        public CheckSeverity $severity,
        public CheckStatus $status,
        public string $title,
        public string $message,
        public array $details = [],
        public array $references = [],
    ) {}

    /**
     * Contrat de persistance — le shape du ConsistencyRunResult,
     * réutilisé par LegalComplianceRunResult avec LegalCheckType.
     *
     * @return array{type: LegalCheckType, severity: CheckSeverity, status: CheckStatus, title: string, message: string, details: array<string, mixed>, references: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'severity' => $this->severity,
            'status' => $this->status,
            'title' => $this->title,
            'message' => $this->message,
            'details' => $this->details,
            'references' => $this->references,
        ];
    }
}
