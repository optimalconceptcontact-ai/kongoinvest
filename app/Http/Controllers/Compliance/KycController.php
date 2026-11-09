<?php

namespace App\Http\Controllers\Compliance;

use App\Enums\ComplianceCaseStatus;
use App\Enums\DocumentType;
use App\Enums\KycStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Compliance\IndividualKycRequest;
use App\Jobs\RunAmlScreeningJob;
use App\Models\ComplianceAuditLog;
use App\Models\ComplianceCase;
use App\Models\User;
use App\Services\Compliance\IdentityDocumentVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class KycController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();
        $kyc = $user->kycProfile()->firstOrCreate([
            'user_id' => $user->id,
        ]);

        $this->authorize('view', $kyc);

        $documents = $kyc->documents()->with('checks')->latest()->get()->map(fn ($doc) => [
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
            'verifiedDocuments' => $documents,
            'biometrics' => $biometrics ? [
                'livenessPassed' => $biometrics->liveness_passed,
                'livenessScore' => $biometrics->liveness_score,
                'faceMatchPassed' => $biometrics->face_match_passed,
                'faceMatchScore' => $biometrics->face_match_score,
            ] : null,
        ]);
    }

    public function submitIndividual(
        IndividualKycRequest $request,
        IdentityDocumentVerificationService $verificationService
    ): RedirectResponse {
        $user = $request->user();
        $kyc = $user->kycProfile()->firstOrCreate(['user_id' => $user->id]);

        $this->authorize('submit', $kyc);

        $data = $request->validated();

        [$kyc, $complianceCase] = DB::transaction(function () use ($kyc, $user, $data, $verificationService, $request) {
            // Scope the compliance case to the applicant and make sure the
            // morph target is a real App\Models\User class (not a relative
            // class name resolved inside this controller's namespace).
            $complianceCase = ComplianceCase::firstOrCreate(
                [
                    'subject_type' => User::class,
                    'subject_id' => $user->id,
                ],
                [
                    'case_number' => 'CASE-IND-'.strtoupper(bin2hex(random_bytes(3))),
                    'user_id' => $user->id,
                ]
            );

            // A blocked or suspended case must never be cleared simply by
            // re-submitting the form: only a compliance officer can lift it.
            if ($complianceCase->isBlockedOrSuspended()) {
                abort(423, 'Votre dossier de conformité fait l\'objet d\'une mesure de blocage ou de suspension. Contactez le service conformité.');
            }

            // Update KYC Profile fields
            $kyc->first_name = $data['first_name'];
            $kyc->last_name = $data['last_name'];
            $kyc->postnom = $data['postnom'] ?? null;
            $kyc->date_of_birth = $data['date_of_birth'];
            $kyc->place_of_birth = $data['place_of_birth'];
            $kyc->nationalities = $data['nationalities'];
            $kyc->residential_address = $data['residential_address'];
            $kyc->residential_country = strtoupper($data['residential_country']);
            $kyc->phone = $data['phone'];
            $kyc->email = $data['email'];
            $kyc->profession = $data['profession'];
            $kyc->employer_or_activity = $data['employer_or_activity'] ?? null;
            $kyc->is_pep = (bool) $data['is_pep'];
            $kyc->pep_function = $data['pep_function'] ?? null;
            $kyc->pep_start_date = $data['pep_start_date'] ?? null;
            $kyc->pep_end_date = $data['pep_end_date'] ?? null;
            $kyc->is_acting_for_third_party = (bool) $data['is_acting_for_third_party'];
            $kyc->third_party_details = $data['third_party_details'] ?? null;

            $kyc->status = KycStatus::Submitted;
            $kyc->submitted_at = now();
            $kyc->compliance_case_id = $complianceCase->id;
            $kyc->save();

            // Verify identity document (private storage + OCR checks).
            $document = $verificationService->verifyDocument(
                $kyc,
                $request->file('front_file'),
                $request->file('back_file'),
                DocumentType::from($data['document_type']),
                $data['issuing_country'],
                $data['document_number'],
                $data['issued_at'] ?? null,
                $data['expires_at'] ?? null,
            );

            // Verify selfie / biometrics if uploaded
            if ($request->hasFile('selfie_file')) {
                $verificationService->verifyBiometrics($kyc, $request->file('selfie_file'), $document);
            }

            $complianceCase->forceFill([
                'status' => ComplianceCaseStatus::AutomatedChecks,
                'submitted_at' => $complianceCase->submitted_at ?? now(),
            ])->save();

            ComplianceAuditLog::create([
                'compliance_case_id' => $complianceCase->id,
                'actor_user_id' => $user->id,
                'event' => 'kyc.submitted',
                'subject_type' => $kyc::class,
                'subject_id' => $kyc->id,
                'context' => [
                    'document_type' => $data['document_type'],
                    'issuing_country' => strtoupper($data['issuing_country']),
                ],
                'legal_retention_required' => true,
                'retention_until' => now()->addYears(config('compliance.legal.retention_years', 10)),
            ]);

            return [$kyc, $complianceCase];
        });

        // Dispatch only once the transaction is committed so the worker can
        // never read a half-written dossier (and a queue failure cannot roll
        // back a valid submission).
        RunAmlScreeningJob::dispatch(
            $complianceCase,
            $kyc->fullName(),
            $kyc->residential_country ?? 'CD',
            'individual'
        );

        return back()->with('success', 'Votre dossier KYC complet a été soumis avec succès pour vérification.');
    }
}
