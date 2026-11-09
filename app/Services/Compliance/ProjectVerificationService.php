<?php

namespace App\Services\Compliance;

use App\Enums\BusinessStatus;
use App\Enums\ComplianceCaseStatus;
use App\Enums\ProjectMaturityStage;
use App\Models\ComplianceCase;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class ProjectVerificationService
{
    /**
     * Initialize or update project verification profile for pre-incorporation projects.
     * Never demands RCCM or NIF for non-incorporated projects!
     */
    public function initializeProjectVerification(
        Project $project,
        ProjectMaturityStage $stage,
        array $projectData = []
    ): Project {
        return DB::transaction(function () use ($project, $stage, $projectData) {
            $user = $project->user;

            // Check founder individual KYC status
            $identityStatus = $user->kycProfile?->isVerifiedAndActive() ? 'verified' : 'pending';

            // Find or create compliance case for the project
            $complianceCase = ComplianceCase::firstOrCreate(
                [
                    'subject_type' => Project::class,
                    'subject_id' => $project->id,
                ],
                [
                    'case_number' => 'CASE-PRJ-'.strtoupper(bin2hex(random_bytes(3))),
                    'user_id' => $user->id,
                    'status' => ComplianceCaseStatus::CollectingInformation,
                ]
            );

            // Determine compliance status for matching
            $complianceStatus = ($identityStatus === 'verified')
                ? 'approved_for_preliminary_matching'
                : 'pending_identity';

            // Pre-incorporation preparation must never erase an existing
            // incorporation fact (a project converted to a company keeps
            // its incorporated business status).
            $businessStatus = $project->business_status === BusinessStatus::Incorporated
                ? BusinessStatus::Incorporated
                : BusinessStatus::NotIncorporated;

            // Update project with orthogonal statuses
            $project->forceFill([
                'maturity_stage' => $stage,
                'identity_status' => $identityStatus,
                'business_status' => $businessStatus,
                'compliance_status' => $complianceStatus,
                'compliance_case_id' => $complianceCase->id,
            ])->save();

            // Track the declared structuring intent when supplied, without
            // requiring RCCM/NIF (the entity is not incorporated yet).
            if (! empty($projectData['intended_company_name']) || ! empty($projectData['intended_legal_form'])) {
                $project->formationReadiness()->updateOrCreate(
                    ['project_id' => $project->id],
                    array_filter([
                        'intended_company_name' => $projectData['intended_company_name'] ?? null,
                        'intended_legal_form' => $projectData['intended_legal_form'] ?? null,
                        'intended_corporate_purpose' => $projectData['intended_corporate_purpose'] ?? null,
                        'intended_registered_office' => $projectData['intended_registered_office'] ?? null,
                        'intended_capital_minor' => $projectData['intended_capital_minor'] ?? null,
                        'intended_capital_currency' => $projectData['intended_capital_currency'] ?? null,
                    ], fn ($value) => $value !== null)
                );
            }

            return $project;
        });
    }

    /**
     * Check financial operation eligibility for the project.
     *
     * @return array{
     *     allowed_actions: array<string>,
     *     blocked_actions: array<string>,
     *     reasons: array<string>
     * }
     */
    public function evaluateEligibility(Project $project): array
    {
        $allowed = [
            'create_and_publish_project',
            'evaluation_by_investment_solutions',
            'investment_readiness_coaching',
            'presentation_to_investors',
            'receive_non_binding_loi',
            'preliminary_discussions',
            'draft_conditional_term_sheet',
        ];

        $blocked = [];
        $reasons = [];

        // Check if incorporated
        if ($project->business_status === BusinessStatus::NotIncorporated) {
            $blocked = [
                'issue_shares_or_equity',
                'entry_on_definitive_cap_table',
                'issue_share_certificate',
                'equity_closing',
                'direct_fund_disbursement',
            ];

            $reasons[] = 'Le projet n\'est pas encore doté d\'une personnalité morale immatriculée (RCCM). Les opérations d\'émission de parts ou d\'actions et de closing equity sont strictement réservées aux sociétés constituées.';
        }

        if ($project->identity_status !== 'verified') {
            $blocked[] = 'open_data_room';
            $reasons[] = 'Le KYC du porteur de projet principal doit être vérifié avant l\'ouverture de la Data Room aux investisseurs.';
        }

        return [
            'allowed_actions' => $allowed,
            'blocked_actions' => $blocked,
            'reasons' => $reasons,
        ];
    }
}
