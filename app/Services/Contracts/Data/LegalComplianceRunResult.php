<?php

namespace App\Services\Contracts\Data;

use App\Enums\CheckSeverity;
use App\Enums\CheckStatus;
use App\Enums\LegalCheckType;

/**
 * Synthèse d'une exécution du LegalComplianceService : un
 * batch de contrôles juridiques déterministes écrits en base et
 * consultables en historique (prompt §10). Même mécanique
 * batch harness que ConsistencyRunResult (contrôles financiers).
 */
final readonly class LegalComplianceRunResult
{
    /**
     * @param  array<string, int>  $counts
     * @param  list<array{type: LegalCheckType, severity: CheckSeverity, status: CheckStatus, title: string, message: string, details: array<string, mixed>, references: array<string, mixed>}>  $checks
     */
    public function __construct(
        public readonly string $batch,
        public readonly array $counts,
        public readonly bool $blocking,
        public readonly array $checks,
    ) {}

    public function statusCount(CheckStatus $status): int
    {
        return $this->counts[$status->value] ?? 0;
    }
}
