<?php

namespace App\Http\Controllers\Compliance;

use App\Enums\BusinessLegalDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Compliance\BusinessKycRequest;
use App\Jobs\RunAmlScreeningJob;
use App\Models\Company;
use App\Services\Compliance\BusinessVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class KybController extends Controller
{
    public function show(Company $company): Response
    {
        $this->authorize('view', $company);

        $verification = $company->latestVerification;
        $documents = $verification?->legalDocuments()->get()->map(fn ($d) => [
            'id' => $d->id,
            'type' => $d->document_type->value,
            'typeLabel' => $d->document_type->label(),
            'title' => $d->document_title,
            'isVerified' => $d->is_verified,
            'issueDate' => $d->issue_date?->format('Y-m-d'),
            'downloadUrl' => URL::temporarySignedRoute(
                'kyb.documents.download',
                now()->addMinutes(10),
                ['document' => $d->id]
            ),
        ]) ?? [];

        return Inertia::render('Compliance/Kyb', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'registrationNumber' => $company->registration_number,
                'legalForm' => $company->legal_form,
            ],
            'verification' => $verification ? [
                'id' => $verification->id,
                'status' => $verification->status,
                'rccm' => $verification->rccm_number,
                'nif' => $verification->tax_identification_number,
                'nationalId' => $verification->national_identification_number,
                'registeredOffice' => $verification->registered_office_address,
                'capitalMinor' => $verification->share_capital_minor,
                'capitalCurrency' => $verification->share_capital_currency,
                'sector' => $verification->industry_sector,
                'legalRepresentative' => $verification->legal_representative_name,
                'verifiedAt' => $verification->verified_at?->toIso8601String(),
                'notes' => $verification->verification_notes,
            ] : null,
            'documents' => $documents,
        ]);
    }

    public function submit(
        BusinessKycRequest $request,
        Company $company,
        BusinessVerificationService $kybService
    ): RedirectResponse {
        $this->authorize('update', $company);

        $verification = $kybService->initiateVerification($company, $request->user(), $request->validated());

        // Attach core documents if uploaded
        if ($request->hasFile('rccm_file')) {
            $kybService->attachDocument(
                $verification,
                $request->file('rccm_file'),
                BusinessLegalDocumentType::Rccm,
                'Extrait RCCM'
            );
        }

        if ($request->hasFile('statutes_file')) {
            $kybService->attachDocument(
                $verification,
                $request->file('statutes_file'),
                BusinessLegalDocumentType::Statutes,
                'Statuts certifiés'
            );
        }

        if ($request->hasFile('nif_file')) {
            $kybService->attachDocument(
                $verification,
                $request->file('nif_file'),
                BusinessLegalDocumentType::Nif,
                'Attestation NIF'
            );
        }

        // Trigger AML screening on the entity
        if ($verification->complianceCase) {
            RunAmlScreeningJob::dispatch(
                $verification->complianceCase,
                $verification->company_name,
                $verification->country_of_incorporation,
                'entity',
                $verification->industry_sector
            );
        }

        return back()->with('success', 'Dossier KYB de l\'entreprise soumis avec succès.');
    }
}
