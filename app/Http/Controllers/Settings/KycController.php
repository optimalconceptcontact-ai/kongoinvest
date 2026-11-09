<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Kyc\SubmitKyc;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class KycController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $kyc = $user->kycProfile()->firstOrCreate([
            'user_id' => $user->id,
        ]);
        $this->authorize('view', $kyc);

        $verifiedDocs = $kyc->documents()->with('checks')->latest()->get()->map(fn ($doc) => [
            'id' => $doc->id,
            'type' => $doc->document_type->value,
            'typeLabel' => $doc->document_type->label(),
            'issuingCountry' => $doc->issuing_country,
            'status' => $doc->status,
            'issuedAt' => $doc->issued_at?->format('Y-m-d'),
            'expiresAt' => $doc->expires_at?->format('Y-m-d'),
            'isExpired' => $doc->isExpired(),
            'downloadUrl' => URL::temporarySignedRoute(
                'kyc.documents.file',
                now()->addMinutes(10),
                ['document' => $doc->id]
            ),
            'check' => $doc->latestCheck ? [
                'isReadable' => $doc->latestCheck->is_readable,
                'ocrScore' => $doc->latestCheck->overall_ocr_score,
                'isDuplicate' => $doc->latestCheck->is_duplicate,
                'inconsistencies' => $doc->latestCheck->inconsistencies_detected ?? [],
            ] : null,
        ]);

        $biometrics = $kyc->biometricChecks()->latest()->first();

        return Inertia::render('Settings/Kyc', [
            'status' => $kyc->status->value,
            'rejectionReason' => $kyc->rejection_reason,
            'submittedAt' => $kyc->submitted_at?->toIso8601String(),
            'documents' => $kyc->getMedia('kyc-documents')->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'downloadUrl' => URL::temporarySignedRoute('kyc.documents.download', now()->addMinutes(10), ['media' => $m->id]),
            ]),
            'profile' => [
                'id' => $kyc->id,
                'status' => $kyc->status->value,
                'statusLabel' => $kyc->status->label(),
                'firstName' => $kyc->first_name,
                'lastName' => $kyc->last_name,
                'postnom' => $kyc->postnom,
                'dateOfBirth' => $kyc->date_of_birth?->format('Y-m-d'),
                'placeOfBirth' => $kyc->place_of_birth,
                'nationalities' => $kyc->nationalities ?? ['CD'],
                'residentialAddress' => $kyc->residential_address,
                'residentialCountry' => $kyc->residential_country ?? 'CD',
                'phone' => $kyc->phone ?? $user->phone,
                'email' => $kyc->email ?? $user->email,
                'profession' => $kyc->profession,
                'employerOrActivity' => $kyc->employer_or_activity,
                'isPep' => $kyc->is_pep,
                'pepFunction' => $kyc->pep_function,
                'isActingForThirdParty' => $kyc->is_acting_for_third_party,
                'rejectionReason' => $kyc->rejection_reason,
                'submittedAt' => $kyc->submitted_at?->toIso8601String(),
                'validUntil' => $kyc->valid_until?->toIso8601String(),
            ],
            'verifiedDocuments' => $verifiedDocs,
            'biometrics' => $biometrics ? [
                'livenessPassed' => $biometrics->liveness_passed,
                'livenessScore' => $biometrics->liveness_score,
                'faceMatchPassed' => $biometrics->face_match_passed,
                'faceMatchScore' => $biometrics->face_match_score,
            ] : null,
        ]);
    }

    public function submit(Request $request, SubmitKyc $action): RedirectResponse
    {
        $kyc = $request->user()->kycProfile()->firstOrCreate([
            'user_id' => $request->user()->id,
        ]);
        $this->authorize('submit', $kyc);

        $data = $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'], // 10MB
        ]);

        $action->handle($kyc, $data['files']);

        return back()->with('success', 'Dossier KYC soumis pour vérification.');
    }
}
