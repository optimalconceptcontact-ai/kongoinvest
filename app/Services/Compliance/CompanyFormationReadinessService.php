<?php

namespace App\Services\Compliance;

use App\Enums\BusinessStatus;
use App\Enums\LegalForm;
use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectFormationReadiness;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompanyFormationReadinessService
{
    /**
     * Prepare or update company formation readiness for a project.
     */
    public function updateReadiness(
        Project $project,
        array $data
    ): ProjectFormationReadiness {
        return DB::transaction(function () use ($project, $data) {
            $cofounders = $data['indicative_cofounders'] ?? [];
            $cofounderCount = max(1, count($cofounders));
            $targetMinor = $project->funding_target_minor ?? ($data['intended_capital_minor'] ?? 0);

            // Recommandation intelligente OHADA
            $recommendation = $this->recommendLegalForm($cofounderCount, $targetMinor, $data['intended_legal_form'] ?? null);

            // Calculate readiness score (0-100)
            $checklist = [
                'name_defined' => ! empty($data['intended_company_name']),
                'purpose_defined' => ! empty($data['intended_corporate_purpose']),
                'headquarters_defined' => ! empty($data['intended_registered_office']),
                'capital_defined' => ($data['intended_capital_minor'] ?? 0) > 0,
                'cofounders_structured' => ! empty($cofounders),
                'legal_form_selected' => ! empty($data['intended_legal_form']),
            ];

            $completedCount = count(array_filter($checklist));
            $score = (int) round(($completedCount / count($checklist)) * 100);

            return ProjectFormationReadiness::updateOrCreate(
                ['project_id' => $project->id],
                [
                    'intended_legal_form' => $data['intended_legal_form'] ?? $recommendation['form'],
                    'intended_company_name' => $data['intended_company_name'] ?? $project->title,
                    'intended_corporate_purpose' => $data['intended_corporate_purpose'] ?? $project->description,
                    'intended_registered_office' => $data['intended_registered_office'] ?? 'Kinshasa, RDC',
                    'intended_capital_minor' => $data['intended_capital_minor'] ?? 100000, // 1,000 USD default
                    'intended_capital_currency' => $data['intended_capital_currency'] ?? 'USD',
                    'indicative_cofounders' => $cofounders,
                    'recommended_legal_form' => $recommendation['form'],
                    'recommendation_rationale' => $recommendation['rationale'],
                    'readiness_score' => $score,
                    'readiness_checklist' => $checklist,
                ]
            );
        });
    }

    /**
     * Logic for OHADA company form recommendation.
     *
     * @return array{form: string, rationale: string}
     */
    public function recommendLegalForm(int $founderCount, int $fundingMinor, ?string $preferredForm = null): array
    {
        // If 1 founder
        if ($founderCount === 1) {
            if ($fundingMinor >= 5000000) { // >= $50,000 USD and seeking external equity
                return [
                    'form' => LegalForm::Sasu->value,
                    'rationale' => 'SASU (Société par Actions Simplifiée Unipersonnelle) : idéale pour un fondateur unique prévoyant l\'ouverture rapide du capital à des investisseurs avec une grande liberté contractuelle statutaire.',
                ];
            }

            return [
                'form' => LegalForm::Sarlu->value,
                'rationale' => 'SARLU (Société à Responsabilité Limitée Unipersonnelle) : forme simple, économique et sécurisante pour démarrer avec un associé unique en droit OHADA.',
            ];
        }

        // Multiple founders
        if ($fundingMinor >= 10000000) { // >= $100,000 USD
            return [
                'form' => LegalForm::Sas->value,
                'rationale' => 'SAS (Société par Actions Simplifiée) : forme recommandée pour les projets à fort potentiel de croissance (Venture Capital), permettant d\'aménager des droits de vote multiples, des pactes d\'actionnaires et des clauses de sortie adaptées.',
            ];
        }

        return [
            'form' => LegalForm::Sarl->value,
            'rationale' => 'SARL (Société à Responsabilité Limitée) : forme standard la plus courante en RDC et zone OHADA pour 2 à 50 associés, offrant une gouvernance encadrée et un capital minimum accessible.',
        ];
    }

    /**
     * Transition a non-incorporated project to an incorporated company.
     * Preserves all history, links new company to project, and initiates KYB.
     */
    public function transitionToIncorporatedCompany(
        Project $project,
        User $founder,
        array $incorporationData, // rccm, nif, company_name, legal_form, capital_minor...
        BusinessVerificationService $kybService
    ): Company {
        return DB::transaction(function () use ($project, $founder, $incorporationData, $kybService) {
            $readiness = $project->formationReadiness;

            // 1. Create or link Company
            $company = Company::create([
                'name' => $incorporationData['company_name'] ?? $readiness?->intended_company_name ?? $project->title,
                'registration_number' => $incorporationData['rccm_number'],
                'legal_form' => $incorporationData['legal_form'] ?? $readiness?->intended_legal_form ?? 'sarl',
                'description' => $project->description,
                'country_code' => 'CD',
                'city' => $incorporationData['city'] ?? 'Kinshasa',
            ]);

            // 2. Mark readiness as converted
            if ($readiness) {
                $readiness->converted_to_company_id = $company->id;
                $readiness->converted_at = now();
                $readiness->save();
            }

            // 3. Update project statuses
            $project->forceFill([
                'business_status' => BusinessStatus::Incorporated,
            ])->save();

            // 4. Initiate KYB process for the new company
            $kybService->initiateVerification($company, $founder, $incorporationData);

            return $company;
        });
    }
}
