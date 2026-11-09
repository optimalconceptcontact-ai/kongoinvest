<?php

namespace App\Services\Compliance;

use App\Enums\AmlAlertStatus;
use App\Enums\AmlAlertType;
use App\Enums\ComplianceCaseStatus;
use App\Models\AmlAlert;
use App\Models\ComplianceApproval;
use App\Models\ComplianceAuditLog;
use App\Models\ComplianceCase;
use App\Models\ComplianceCaseAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ComplianceCaseManagementService
{
    /**
     * Transition case status with full audit logging.
     */
    public function transitionStatus(
        ComplianceCase $case,
        ComplianceCaseStatus $newStatus,
        ?User $actor = null,
        ?string $comment = null,
        ?array $payload = null
    ): ComplianceCase {
        return DB::transaction(function () use ($case, $newStatus, $actor, $comment, $payload) {
            $oldStatus = $case->status;

            // A blocked/suspended case can never be approved, even if a
            // caller reaches transitionStatus() directly.
            if (
                in_array($newStatus, [ComplianceCaseStatus::Approved, ComplianceCaseStatus::ApprovedWithConditions], true)
                && $case->isBlockedOrSuspended()
            ) {
                throw ValidationException::withMessages([
                    'case' => 'Un dossier bloqué ou suspendu ne peut pas être approuvé avant traitement des alertes.',
                ]);
            }

            $case->forceFill([
                'status' => $newStatus,
            ]);

            if ($newStatus === ComplianceCaseStatus::Suspended) {
                $case->suspended_at = now();
                $case->suspension_or_block_reason = $comment;
            } elseif ($newStatus === ComplianceCaseStatus::Blocked) {
                $case->blocked_at = now();
                $case->suspension_or_block_reason = $comment;
            } elseif (in_array($newStatus, [ComplianceCaseStatus::Approved, ComplianceCaseStatus::ApprovedWithConditions], true)) {
                $case->reviewed_at = now();
                $case->expires_at = now()->addYear();
            }

            $case->save();

            // Record action in history
            ComplianceCaseAction::create([
                'compliance_case_id' => $case->id,
                'actor_user_id' => $actor?->id,
                'action_type' => 'status_change',
                'from_status' => $oldStatus->value,
                'to_status' => $newStatus->value,
                'comment' => $comment,
                'payload' => $payload,
            ]);

            // Compliance audit log
            ComplianceAuditLog::create([
                'compliance_case_id' => $case->id,
                'actor_user_id' => $actor?->id,
                'event' => 'case.status_changed',
                'subject_type' => ComplianceCase::class,
                'subject_id' => $case->id,
                'context' => [
                    'from' => $oldStatus->value,
                    'to' => $newStatus->value,
                    'comment' => $comment,
                ],
                'legal_retention_required' => true,
                'retention_until' => now()->addYears(config('compliance.legal.retention_years', 10)),
            ]);

            return $case;
        });
    }

    /**
     * Formal approval (or conditional approval) of a case.
     */
    public function approveCase(
        ComplianceCase $case,
        User $officer,
        string $rationale,
        ?string $conditions = null,
        ?string $validUntil = null
    ): ComplianceApproval {
        return DB::transaction(function () use ($case, $officer, $rationale, $conditions, $validUntil) {
            if ($case->isBlockedOrSuspended()) {
                throw ValidationException::withMessages([
                    'case' => 'Un dossier bloqué ou suspendu ne peut pas être approuvé.',
                ]);
            }

            $hasOpenCriticalAlert = $case->alerts()
                ->where('status', AmlAlertStatus::Open)
                ->where(function ($query) {
                    $query->where('alert_type', AmlAlertType::SanctionsMatch)
                        ->orWhere('severity', 'critical');
                })
                ->exists();

            if ($hasOpenCriticalAlert) {
                throw ValidationException::withMessages([
                    'case' => 'Des alertes AML critiques (sanctions) restent ouvertes : elles doivent être traitées avant toute approbation.',
                ]);
            }

            $decision = $conditions ? 'approved_with_conditions' : 'approved';
            $status = $conditions ? ComplianceCaseStatus::ApprovedWithConditions : ComplianceCaseStatus::Approved;

            $approval = ComplianceApproval::create([
                'compliance_case_id' => $case->id,
                'approved_by_user_id' => $officer->id,
                'decision' => $decision,
                'conditions' => $conditions,
                'rationale' => $rationale,
                'valid_until' => $validUntil ?? now()->addYear(),
            ]);

            $this->transitionStatus(
                $case,
                $status,
                $officer,
                "Approbation conformité : {$rationale}"
            );

            return $approval;
        });
    }

    /**
     * Constitute a Suspicious Activity Report / Déclaration d'Opération Suspecte (DOS) dossier.
     * Note: An alert or AML score NEVER automatically transmits a DOS!
     * This prepares the analysis file, maintains tipping-off confidentiality, and logs audit trail.
     *
     * @return array{
     *     dos_reference: string,
     *     case_number: string,
     *     constituted_at: string,
     *     officer_name: string,
     *     suspect_entity: array<string, mixed>,
     *     suspicious_indicators: array<string>,
     *     factual_narrative: string,
     *     confidentiality_rule: string,
     *     ready_for_authorized_declarant: bool
     * }
     */
    public function prepareSuspiciousActivityDossier(
        ComplianceCase $case,
        User $officer,
        string $factualNarrative,
        array $selectedAlertIds
    ): array {
        return DB::transaction(function () use ($case, $officer, $factualNarrative, $selectedAlertIds) {
            $alerts = AmlAlert::whereIn('id', $selectedAlertIds)
                ->where('compliance_case_id', $case->id)
                ->get();

            if ($alerts->isEmpty()) {
                throw ValidationException::withMessages([
                    'alert_ids' => 'Aucune alerte sélectionnée ne correspond à ce dossier.',
                ]);
            }

            $indicators = $alerts->pluck('title')->all();

            $dosReference = 'DOS-CENAREF-PREP-'.strtoupper(bin2hex(random_bytes(4)));

            // Record action
            ComplianceCaseAction::create([
                'compliance_case_id' => $case->id,
                'actor_user_id' => $officer->id,
                'action_type' => 'dos_dossier_constituted',
                'comment' => "Constitution du dossier d'analyse de soupçon {$dosReference}. Strictly Confidential.",
                'payload' => [
                    'dos_reference' => $dosReference,
                    'alerts_included' => $selectedAlertIds,
                    'indicators' => $indicators,
                ],
            ]);

            // Audit log with 10-year retention
            ComplianceAuditLog::create([
                'compliance_case_id' => $case->id,
                'actor_user_id' => $officer->id,
                'event' => 'dos.dossier_prepared',
                'subject_type' => ComplianceCase::class,
                'subject_id' => $case->id,
                'context' => [
                    'dos_reference' => $dosReference,
                    'officer' => $officer->name,
                ],
                'legal_retention_required' => true,
                'retention_until' => now()->addYears(10),
            ]);

            return [
                'dos_reference' => $dosReference,
                'case_number' => $case->case_number,
                'constituted_at' => now()->toIso8601String(),
                'officer_name' => $officer->name,
                'suspect_entity' => [
                    'subject_type' => $case->subject_type,
                    'subject_id' => $case->subject_id,
                    'user_email' => $case->user?->email,
                ],
                'suspicious_indicators' => $indicators,
                'factual_narrative' => $factualNarrative,
                'confidentiality_rule' => 'Interdiction absolue de divulgation au client (Article 35 Loi n° 22/068 - Anti-tipping off).',
                'ready_for_authorized_declarant' => true,
            ];
        });
    }
}
