<?php

namespace App\Http\Controllers\Compliance;

use App\Enums\ProjectMaturityStage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Compliance\PreIncorporationReadinessRequest;
use App\Models\Project;
use App\Services\Compliance\BusinessVerificationService;
use App\Services\Compliance\CompanyFormationReadinessService;
use App\Services\Compliance\ProjectVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PreIncorporationReadinessController extends Controller
{
    public function show(
        Project $project,
        ProjectVerificationService $verificationService
    ): Response {
        $this->authorize('update', $project);

        $readiness = $project->formationReadiness;
        $eligibility = $verificationService->evaluateEligibility($project);

        return Inertia::render('Compliance/PreIncorporationReadiness', [
            'project' => [
                'id' => $project->id,
                'title' => $project->title,
                'slug' => $project->slug,
                'stage' => $project->maturity_stage?->value ?? 'idea',
                'stageLabel' => $project->maturity_stage?->label() ?? 'Idée',
                'identityStatus' => $project->identity_status,
                'businessStatus' => $project->business_status?->value ?? 'not_incorporated',
                'businessStatusLabel' => $project->business_status?->label() ?? 'Non constitué',
                'complianceStatus' => $project->compliance_status,
                'fundingTargetMinor' => $project->funding_target_minor,
            ],
            'readiness' => $readiness ? [
                'id' => $readiness->id,
                'intendedLegalForm' => $readiness->intended_legal_form,
                'intendedCompanyName' => $readiness->intended_company_name,
                'intendedPurpose' => $readiness->intended_corporate_purpose,
                'intendedOffice' => $readiness->intended_registered_office,
                'intendedCapitalMinor' => $readiness->intended_capital_minor,
                'indicativeCofounders' => $readiness->indicative_cofounders ?? [],
                'recommendedLegalForm' => $readiness->recommended_legal_form,
                'recommendationRationale' => $readiness->recommendation_rationale,
                'readinessScore' => $readiness->readiness_score,
                'readinessChecklist' => $readiness->readiness_checklist,
                'isConverted' => $readiness->isConverted(),
            ] : null,
            'eligibility' => $eligibility,
        ]);
    }

    public function update(
        PreIncorporationReadinessRequest $request,
        Project $project,
        CompanyFormationReadinessService $readinessService,
        ProjectVerificationService $verificationService
    ): RedirectResponse {
        $this->authorize('update', $project);

        $data = $request->validated();

        if (! empty($data['maturity_stage'])) {
            $verificationService->initializeProjectVerification(
                $project,
                ProjectMaturityStage::from($data['maturity_stage']),
                $data
            );
        }

        $readinessService->updateReadiness($project, $data);

        return back()->with('success', 'Préparation juridique et structuration prévisionnelle mises à jour.');
    }

    public function convertToCompany(
        Request $request,
        Project $project,
        CompanyFormationReadinessService $readinessService,
        BusinessVerificationService $kybService
    ): RedirectResponse {
        $this->authorize('update', $project);

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:200'],
            'rccm_number' => ['required', 'string', 'max:100'],
            'legal_form' => ['required', 'string'],
            'tax_identification_number' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
        ]);

        $company = $readinessService->transitionToIncorporatedCompany(
            $project,
            $request->user(),
            $data,
            $kybService
        );

        return redirect()->route('kyb.show', $company->id)->with('success', 'Félicitations ! Votre projet est désormais rattaché à une entreprise immatriculée. Le parcours KYB est initialisé.');
    }
}
