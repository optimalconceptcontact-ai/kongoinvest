<?php

namespace App\Services\Compliance;

use App\Contracts\Compliance\AmlScreeningProviderContract;
use App\Enums\AmlAlertStatus;
use App\Enums\AmlAlertType;
use App\Enums\AmlRiskLevel;
use App\Enums\ComplianceCaseStatus;
use App\Models\AdverseMediaScreening;
use App\Models\AmlAlert;
use App\Models\ComplianceAuditLog;
use App\Models\ComplianceCase;
use App\Models\ComplianceProviderCheck;
use App\Models\PepScreening;
use App\Models\SanctionsScreening;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AmlScreeningService
{
    public function __construct(
        private AmlScreeningProviderContract $provider,
    ) {}

    /**
     * Perform complete AML screening for a compliance case subject.
     *
     * A name match is only a precautionary signal: it never confirms a
     * person as sanctioned/PEP and it never triggers a DOS automatically.
     */
    public function performScreening(
        ComplianceCase $case,
        string $subjectName,
        string $country = 'CD',
        string $subjectType = 'individual',
        ?string $sector = null
    ): array {
        return DB::transaction(function () use ($case, $subjectName, $country, $subjectType, $sector) {
            $startedAt = microtime(true);

            $result = $this->provider->screen($subjectName, $country, $subjectType);

            ComplianceProviderCheck::create([
                'compliance_case_id' => $case->id,
                'provider' => $result['provider'] ?? 'unknown',
                'check_type' => 'aml_screening',
                'status' => 'completed',
                'request_payload' => [
                    'subject_name' => $subjectName,
                    'country' => $country,
                    'subject_type' => $subjectType,
                    'sector' => $sector,
                ],
                'response_payload' => $result,
                'execution_time_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);

            // 1. Record PEP Screening
            $pepRecord = PepScreening::create([
                'compliance_case_id' => $case->id,
                'subject_name' => $subjectName,
                'subject_country' => $country,
                'provider' => $result['provider'],
                'has_match' => $result['pep']['has_match'],
                'match_type' => $result['pep']['match_type'],
                'match_score' => $result['pep']['match_score'],
                'pep_role_title' => $result['pep']['role_title'],
                'pep_jurisdiction' => $result['pep']['jurisdiction'],
                'raw_matches' => $result['pep']['matches'],
                'is_false_positive' => false,
            ]);

            // If PEP hit, raise Alert + enhanced due diligence
            if ($pepRecord->has_match) {
                $this->raiseAlert($case, [
                    'alert_type' => AmlAlertType::PepMatch,
                    'severity' => AmlRiskLevel::High,
                    'source' => $result['provider'],
                    'title' => "Correspondance PEP détectée pour {$subjectName}",
                    'description' => "Le sujet présente une correspondance de nom avec une Personne Politiquement Exposée ({$pepRecord->pep_role_title}). Vigilance renforcée (EDD) requise ; une confirmation humaine reste obligatoire.",
                    'metadata' => $result['pep'],
                ]);

                $case->forceFill([
                    'status' => ComplianceCaseStatus::EnhancedDueDiligence,
                    'risk_level' => AmlRiskLevel::High,
                ])->save();
            }

            // 2. Record Sanctions Screening
            $sanctionsRecord = SanctionsScreening::create([
                'compliance_case_id' => $case->id,
                'subject_name' => $subjectName,
                'subject_type' => $subjectType,
                'provider' => $result['provider'],
                'has_match' => $result['sanctions']['has_match'],
                'match_score' => $result['sanctions']['match_score'],
                'matched_lists' => $result['sanctions']['matched_lists'],
                'raw_matches' => $result['sanctions']['matches'],
                'is_false_positive' => false,
            ]);

            // A sanctions name match is a precautionary block, NOT a legal
            // confirmation. A compliance officer must review the alert and
            // resolve it before the case can move forward.
            if ($sanctionsRecord->has_match) {
                $this->raiseAlert($case, [
                    'alert_type' => AmlAlertType::SanctionsMatch,
                    'severity' => AmlRiskLevel::Critical,
                    'source' => $result['provider'],
                    'title' => "ALERTE SANCTIONS : correspondance de nom pour {$subjectName}",
                    'description' => 'Correspondance de nom avec les listes : '.implode(', ', $result['sanctions']['matched_lists']).'. Mesure conservatoire de blocage en attente de vérification humaine (homonymie possible).',
                    'metadata' => $result['sanctions'],
                ]);

                $case->forceFill([
                    'status' => ComplianceCaseStatus::Blocked,
                    'risk_level' => AmlRiskLevel::Critical,
                    'blocked_at' => now(),
                    'suspension_or_block_reason' => 'Correspondance de nom avec des listes de sanctions, à confirmer par un analyste conformité.',
                ])->save();
            }

            // 3. Record Adverse Media Screening
            $mediaRecord = AdverseMediaScreening::create([
                'compliance_case_id' => $case->id,
                'subject_name' => $subjectName,
                'provider' => $result['provider'],
                'has_hits' => $result['adverse_media']['has_hits'],
                'hit_count' => $result['adverse_media']['hit_count'],
                'hits_details' => $result['adverse_media']['hits'],
                'is_reviewed' => false,
            ]);

            if ($mediaRecord->has_hits) {
                $this->raiseAlert($case, [
                    'alert_type' => AmlAlertType::AdverseMedia,
                    'severity' => AmlRiskLevel::Medium,
                    'source' => $result['provider'],
                    'title' => "Articles défavorables identifiés pour {$subjectName}",
                    'description' => "{$mediaRecord->hit_count} article(s) de presse défavorable(s) détecté(s).",
                    'metadata' => $result['adverse_media'],
                ]);
            }

            // 4. Geographic Risk Check
            $highRiskCountries = config('compliance.high_risk_countries', ['KP', 'IR', 'MM']);
            if (in_array(strtoupper($country), $highRiskCountries, true)) {
                $this->raiseAlert($case, [
                    'alert_type' => AmlAlertType::HighRiskJurisdiction,
                    'severity' => AmlRiskLevel::High,
                    'source' => 'system_geographic_rule',
                    'title' => "Juridiction à haut risque : {$country}",
                    'description' => "Le sujet ou l'opération implique une juridiction surveillée ou non coopérative (GAFI / FATF).",
                ]);
            }

            // 5. Sectoral Risk Check
            $highRiskSectors = config('compliance.high_risk_sectors', ['mining_extractive', 'precious_metals_stones']);
            if ($sector && in_array(mb_strtolower($sector), $highRiskSectors, true)) {
                $this->raiseAlert($case, [
                    'alert_type' => AmlAlertType::HighRiskSector,
                    'severity' => AmlRiskLevel::Medium,
                    'source' => 'system_sectoral_rule',
                    'title' => "Secteur économique à risque : {$sector}",
                    'description' => "L'activité s'exerce dans un secteur nécessitant une vigilance renforcée selon la réglementation congolaise.",
                ]);
            }

            ComplianceAuditLog::create([
                'compliance_case_id' => $case->id,
                'actor_user_id' => null,
                'event' => 'aml.screening_completed',
                'subject_type' => $case::class,
                'subject_id' => $case->id,
                'context' => [
                    'provider' => $result['provider'],
                    'subject_name' => $subjectName,
                    'country' => $country,
                    'pep_match' => (bool) $result['pep']['has_match'],
                    'sanctions_match' => (bool) $result['sanctions']['has_match'],
                    'adverse_media_hits' => (bool) $result['adverse_media']['has_hits'],
                ],
                'legal_retention_required' => true,
                'retention_until' => now()->addYears(config('compliance.legal.retention_years', 10)),
            ]);

            return [
                'pep' => $pepRecord,
                'sanctions' => $sanctionsRecord,
                'adverse_media' => $mediaRecord,
            ];
        });
    }

    /**
     * Resolve an AML alert (mark as false positive, confirm, dismiss or
     * escalate). Resolution is always a human, audited act.
     */
    public function resolveAlert(
        AmlAlert $alert,
        string $resolutionDecision, // 'false_positive', 'confirmed_risk', 'dismissed', 'escalated_for_dos'
        User $officer,
        string $comment
    ): AmlAlert {
        return DB::transaction(function () use ($alert, $resolutionDecision, $officer, $comment) {
            $alert->status = $resolutionDecision === 'false_positive' ? AmlAlertStatus::FalsePositive : AmlAlertStatus::Resolved;
            $alert->resolution_decision = $resolutionDecision;
            $alert->resolution_comment = $comment;
            $alert->assigned_to_user_id = $officer->id;
            $alert->resolved_at = now();
            $alert->save();

            $case = $alert->complianceCase;

            // A blocked case is only lifted when NO other open sanctions
            // alert remains. Resolving one homonym must not silently
            // unblock a case that has another confirmed match.
            if (
                $alert->alert_type === AmlAlertType::SanctionsMatch
                && $resolutionDecision === 'false_positive'
                && $case !== null
                && ! $this->hasOpenSanctionsAlert($case, $alert->id)
            ) {
                $case->forceFill([
                    'status' => ComplianceCaseStatus::ManualReview,
                    'blocked_at' => null,
                    'suspension_or_block_reason' => null,
                ])->save();
            }

            if ($case !== null) {
                ComplianceAuditLog::create([
                    'compliance_case_id' => $case->id,
                    'actor_user_id' => $officer->id,
                    'event' => 'aml.alert_resolved',
                    'subject_type' => AmlAlert::class,
                    'subject_id' => $alert->id,
                    'context' => [
                        'alert_type' => $alert->alert_type->value,
                        'decision' => $resolutionDecision,
                        'comment' => $comment,
                        'resolved_by' => $officer->name,
                    ],
                    'legal_retention_required' => true,
                    'retention_until' => now()->addYears(config('compliance.legal.retention_years', 10)),
                ]);
            }

            return $alert;
        });
    }

    /**
     * Create an alert only if no open alert of the same type already exists
     * on the case. Prevents duplicate alerts when screening is replayed.
     */
    private function raiseAlert(ComplianceCase $case, array $attributes): ?AmlAlert
    {
        $alreadyOpen = AmlAlert::query()
            ->where('compliance_case_id', $case->id)
            ->where('alert_type', $attributes['alert_type'])
            ->where('status', AmlAlertStatus::Open)
            ->exists();

        if ($alreadyOpen) {
            return null;
        }

        return AmlAlert::create([
            'compliance_case_id' => $case->id,
            'alert_type' => $attributes['alert_type'],
            'severity' => $attributes['severity'],
            'status' => AmlAlertStatus::Open,
            'source' => $attributes['source'],
            'title' => $attributes['title'],
            'description' => $attributes['description'],
            'metadata' => $attributes['metadata'] ?? null,
        ]);
    }

    private function hasOpenSanctionsAlert(ComplianceCase $case, ?int $exceptAlertId = null): bool
    {
        return AmlAlert::query()
            ->where('compliance_case_id', $case->id)
            ->where('alert_type', AmlAlertType::SanctionsMatch)
            ->where('status', AmlAlertStatus::Open)
            ->when($exceptAlertId !== null, fn ($query) => $query->whereKeyNot($exceptAlertId))
            ->exists();
    }
}
