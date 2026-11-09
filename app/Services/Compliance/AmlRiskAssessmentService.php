<?php

namespace App\Services\Compliance;

use App\Enums\AmlAlertStatus;
use App\Enums\AmlAlertType;
use App\Enums\AmlRiskLevel;
use App\Enums\ComplianceCaseStatus;
use App\Models\AmlRiskAssessment;
use App\Models\Company;
use App\Models\ComplianceCase;
use App\Models\ComplianceRuleVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AmlRiskAssessmentService
{
    /**
     * Compute comprehensive AML risk assessment for a compliance case.
     */
    public function assessCase(
        ComplianceCase $case,
        ?User $assessor = null,
        ?string $analystRationale = null
    ): AmlRiskAssessment {
        return DB::transaction(function () use ($case, $assessor, $analystRationale) {
            // Get or create active rule version
            $ruleVersion = ComplianceRuleVersion::where('is_active', true)->latest()->first()
                ?? $this->createDefaultRuleVersion();

            $weights = $ruleVersion->weights;

            // 1. Calculate Client Nature Score (0 - 100)
            $clientScore = 15; // Baseline low risk
            $openAlerts = $case->alerts()->where('status', AmlAlertStatus::Open)->get();
            $hasPepAlert = $openAlerts->contains('alert_type', AmlAlertType::PepMatch);
            $hasSanctionsAlert = $openAlerts->contains('alert_type', AmlAlertType::SanctionsMatch);

            if ($hasPepAlert) {
                $clientScore += 40; // PEP presence triggers higher client risk
            }
            if ($hasSanctionsAlert) {
                $clientScore = 100;
            }

            // 2. Calculate UBO Complexity Score (0 - 100)
            $uboScore = 10;
            if ($case->subject_type === Company::class) {
                $company = $case->subject;
                $verification = $company?->latestVerification;
                if ($verification) {
                    $relationshipCount = $verification->ownershipRelationships()->count();
                    $tierLevels = $verification->ownershipRelationships()->max('tier_level') ?? 1;

                    if ($tierLevels >= 3) {
                        $uboScore += 45; // Multi-layered holding structure
                    } elseif ($tierLevels == 2) {
                        $uboScore += 25;
                    }

                    if ($verification->beneficialOwners()->where('is_pep', true)->exists()) {
                        $uboScore += 30;
                    }
                }
            }

            // 3. Calculate Geographic Risk Score (0 - 100)
            $geoScore = 20;
            if ($openAlerts->contains('alert_type', AmlAlertType::HighRiskJurisdiction)) {
                $geoScore = 80;
            }

            // 4. Calculate Sectoral Risk Score (0 - 100)
            $sectorScore = 15;
            if ($openAlerts->contains('alert_type', AmlAlertType::HighRiskSector)) {
                $sectorScore = 70;
            }

            // 5. Calculate Product & Channel Risk Score (0 - 100)
            $productChannelScore = 20; // Direct digital platform onboarding

            // 6. Calculate Source of Funds Score (0 - 100)
            $sofScore = 15;
            if ($openAlerts->contains('alert_type', AmlAlertType::InadequateSourceOfFunds)) {
                $sofScore = 75;
            }

            // 7. Calculate Transaction Behavior Score (0 - 100)
            $txScore = 10;
            if ($openAlerts->contains('alert_type', AmlAlertType::TransactionStructuring)) {
                $txScore = 85;
            }

            // Weighted aggregation
            $weightedSum = (
                ($clientScore * ($weights['client_nature'] ?? 20)) +
                ($uboScore * ($weights['ubo_complexity'] ?? 15)) +
                ($geoScore * ($weights['geographic'] ?? 15)) +
                ($sectorScore * ($weights['sectoral'] ?? 15)) +
                ($productChannelScore * ($weights['product_channel'] ?? 10)) +
                ($sofScore * ($weights['source_of_funds'] ?? 15)) +
                ($txScore * ($weights['transaction_behavior'] ?? 10))
            ) / 100;

            $overallScore = (int) round($weightedSum);

            // Determine risk level based on thresholds
            $thresholds = $ruleVersion->thresholds;
            if ($hasSanctionsAlert || $overallScore >= ($thresholds['critical'] ?? 90)) {
                $riskLevel = AmlRiskLevel::Critical;
            } elseif ($hasPepAlert || $overallScore >= ($thresholds['high'] ?? 75)) {
                $riskLevel = AmlRiskLevel::High;
            } elseif ($overallScore >= ($thresholds['medium'] ?? 50)) {
                $riskLevel = AmlRiskLevel::Medium;
            } else {
                $riskLevel = AmlRiskLevel::Low;
            }

            $requiresEdd = $riskLevel->requiresEnhancedDueDiligence();

            // Create assessment record
            $assessment = AmlRiskAssessment::create([
                'compliance_case_id' => $case->id,
                'rule_version_id' => $ruleVersion->id,
                'client_nature_score' => $clientScore,
                'ubo_complexity_score' => $uboScore,
                'geographic_score' => $geoScore,
                'sectoral_score' => $sectorScore,
                'product_channel_score' => $productChannelScore,
                'source_of_funds_score' => $sofScore,
                'transaction_behavior_score' => $txScore,
                'overall_score' => $overallScore,
                'risk_level' => $riskLevel,
                'requires_enhanced_due_diligence' => $requiresEdd,
                'mitigating_factors' => $hasPepAlert ? 'PEP déclarée avec fonction transparente et documentation d\'origine du patrimoine fournie.' : null,
                'analyst_rationale' => $analystRationale ?? 'Évaluation automatique des risques selon la matrice RBA v1.',
                'assessed_by_user_id' => $assessor?->id,
                'assessed_at' => now(),
            ]);

            // Sync with compliance case
            $case->forceFill([
                'risk_level' => $riskLevel,
                'risk_score' => $overallScore,
                'rule_version_id' => $ruleVersion->id,
            ])->save();

            if ($requiresEdd && $case->status === ComplianceCaseStatus::AutomatedChecks) {
                $case->forceFill([
                    'status' => ComplianceCaseStatus::EnhancedDueDiligence,
                ])->save();
            }

            return $assessment;
        });
    }

    /**
     * Helper to initialize default rule version if none exists.
     */
    public function createDefaultRuleVersion(): ComplianceRuleVersion
    {
        return ComplianceRuleVersion::firstOrCreate(
            ['version' => config('compliance.risk_assessment.current_version', '1.0.0')],
            [
                'description' => 'Matrice de risque AML/CFT conforme Loi n° 22/068 et Loi n° 25/048 (RDC).',
                'weights' => config('compliance.risk_assessment.weights'),
                'thresholds' => config('compliance.risk_assessment.thresholds'),
                'is_active' => true,
            ]
        );
    }
}
