<?php

namespace App\Services\Compliance;

use App\Contracts\Compliance\BiometricProviderContract;
use App\Contracts\Compliance\OcrProviderContract;
use App\Enums\ComplianceCaseStatus;
use App\Enums\DocumentType;
use App\Enums\KycStatus;
use App\Models\ComplianceAuditLog;
use App\Models\ComplianceProviderCheck;
use App\Models\KycBiometricCheck;
use App\Models\KycDocument;
use App\Models\KycDocumentCheck;
use App\Models\KycProfile;
use App\Models\KycReviewDecision;
use App\Models\KycVerification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IdentityDocumentVerificationService
{
    public function __construct(
        private OcrProviderContract $ocrProvider,
        private BiometricProviderContract $biometricProvider,
    ) {}

    /**
     * Store and verify an identity document.
     */
    public function verifyDocument(
        KycProfile $profile,
        UploadedFile $frontFile,
        ?UploadedFile $backFile,
        DocumentType $documentType,
        string $issuingCountry,
        string $documentNumber,
        ?string $issuedAt = null,
        ?string $expiresAt = null,
        ?string $operationReference = null,
    ): KycDocument {
        // Enforce front file upload
        $frontHash = hash_file('sha256', $frontFile->getRealPath());
        $documentNumberHash = hash('sha256', strtoupper(trim($documentNumber)).':'.strtoupper($issuingCountry));

        // Check for duplicates
        $isDuplicateNumber = KycDocument::where('document_number_hash', $documentNumberHash)
            ->where('kyc_profile_id', '!=', $profile->id)
            ->exists();

        $isDuplicateFile = KycDocument::where('file_hash', $frontHash)
            ->where('kyc_profile_id', '!=', $profile->id)
            ->exists();

        // Store file privately
        $frontPath = $frontFile->store('kyc-documents/front', 'private');
        $backPath = $backFile ? $backFile->store('kyc-documents/back', 'private') : null;

        return DB::transaction(function () use (
            $profile,
            $documentType,
            $issuingCountry,
            $documentNumber,
            $documentNumberHash,
            $frontHash,
            $frontPath,
            $backPath,
            $issuedAt,
            $expiresAt,
            $operationReference,
            $isDuplicateNumber,
            $isDuplicateFile
        ) {
            // Get or create verification session (one per profile, never
            // keyed on the mutable status column or a second upload would
            // open a brand-new session).
            $verification = KycVerification::firstOrCreate(
                [
                    'kyc_profile_id' => $profile->id,
                ],
                [
                    'compliance_case_id' => $profile->compliance_case_id,
                    'operation_reference' => $operationReference,
                ]
            );

            // Create document record
            $document = KycDocument::create([
                'kyc_profile_id' => $profile->id,
                'kyc_verification_id' => $verification->id,
                'document_type' => $documentType,
                'issuing_country' => strtoupper($issuingCountry),
                'document_number_encrypted' => $documentNumber,
                'document_number_hash' => $documentNumberHash,
                'issued_at' => $issuedAt,
                'expires_at' => $expiresAt,
                'front_file_path' => $frontPath,
                'back_file_path' => $backPath,
                'file_hash' => $frontHash,
                'status' => 'uploaded',
            ]);

            // Run OCR extraction & checks
            $ocrStartedAt = microtime(true);
            $ocrResult = $this->ocrProvider->processDocument($document);

            if ($profile->compliance_case_id !== null) {
                ComplianceProviderCheck::create([
                    'compliance_case_id' => $profile->compliance_case_id,
                    'provider' => $ocrResult['provider'],
                    'check_type' => 'ocr',
                    'reference_id' => 'kyc-document-'.$document->id,
                    'status' => 'completed',
                    'request_payload' => [
                        'document_id' => $document->id,
                        'document_type' => $documentType->value,
                        'issuing_country' => strtoupper($issuingCountry),
                    ],
                    'response_payload' => [
                        'is_readable' => $ocrResult['is_readable'],
                        'is_expired' => $ocrResult['is_expired'],
                        'tampering_detected' => $ocrResult['tampering_detected'],
                        'overall_ocr_score' => $ocrResult['overall_ocr_score'],
                        'inconsistencies' => $ocrResult['inconsistencies'],
                    ],
                    'execution_time_ms' => (int) round((microtime(true) - $ocrStartedAt) * 1000),
                ]);
            }

            // Cross-check form inputs with extracted fields
            $inconsistencies = $ocrResult['inconsistencies'];
            $dataMatchesForm = true;

            if (! empty($profile->first_name) && ! empty($ocrResult['extracted_fields']['first_name'])) {
                if (levenshtein(mb_strtolower($profile->first_name), mb_strtolower($ocrResult['extracted_fields']['first_name'])) > 3) {
                    $inconsistencies[] = 'Incohérence détectée sur le prénom.';
                    $dataMatchesForm = false;
                }
            }

            if (! empty($profile->last_name) && ! empty($ocrResult['extracted_fields']['last_name'])) {
                if (levenshtein(mb_strtolower($profile->last_name), mb_strtolower($ocrResult['extracted_fields']['last_name'])) > 3) {
                    $inconsistencies[] = 'Incohérence détectée sur le nom.';
                    $dataMatchesForm = false;
                }
            }

            // Expiration check
            $isExpired = $document->isExpired() || $ocrResult['is_expired'];

            $isDuplicate = $isDuplicateNumber || $isDuplicateFile;
            if ($isDuplicate) {
                $inconsistencies[] = 'Document ou numéro d\'identification déjà utilisé dans un autre dossier.';
            }

            // Record document checks
            KycDocumentCheck::create([
                'kyc_document_id' => $document->id,
                'provider' => $ocrResult['provider'],
                'is_readable' => $ocrResult['is_readable'],
                'is_expired' => $isExpired,
                'is_duplicate' => $isDuplicate,
                'tampering_detected' => $ocrResult['tampering_detected'],
                'data_matches_form' => $dataMatchesForm,
                'extracted_fields' => $ocrResult['extracted_fields'],
                'field_confidence_scores' => $ocrResult['field_confidence_scores'],
                'overall_ocr_score' => $ocrResult['overall_ocr_score'],
                'inconsistencies_detected' => $inconsistencies,
                'check_summary' => $ocrResult['summary'],
            ]);

            $document->status = 'processing';
            $document->save();

            // OCR confidence is recorded but NEVER turns into an automatic
            // verification: a human decision is always required.
            $verification->forceFill([
                'status' => 'processing',
                'overall_confidence_score' => (int) round(((float) $ocrResult['overall_ocr_score']) * 100),
                'requires_manual_review' => true,
            ])->save();

            // Compliance Case status progression
            if ($profile->complianceCase) {
                $profile->complianceCase->forceFill([
                    'status' => ComplianceCaseStatus::AutomatedChecks,
                ])->save();
            }

            return $document;
        });
    }

    /**
     * Process biometric comparison (selfie vs document and liveness).
     */
    public function verifyBiometrics(
        KycProfile $profile,
        UploadedFile $selfieFile,
        ?KycDocument $document = null,
    ): KycBiometricCheck {
        $selfiePath = $selfieFile->store('kyc-biometrics/selfies', 'private');
        $bioStartedAt = microtime(true);
        $bioResult = $this->biometricProvider->verifyBiometrics($profile, $selfiePath, $document);

        if ($profile->compliance_case_id !== null) {
            ComplianceProviderCheck::create([
                'compliance_case_id' => $profile->compliance_case_id,
                'provider' => $bioResult['provider'],
                'check_type' => 'biometric',
                'reference_id' => $document ? 'kyc-document-'.$document->id : 'kyc-profile-'.$profile->id,
                'status' => 'completed',
                'request_payload' => [
                    'kyc_profile_id' => $profile->id,
                    'kyc_document_id' => $document?->id,
                ],
                'response_payload' => [
                    'liveness_passed' => $bioResult['liveness_passed'],
                    'liveness_score' => $bioResult['liveness_score'],
                    'face_match_passed' => $bioResult['face_match_passed'],
                    'face_match_score' => $bioResult['face_match_score'],
                ],
                'execution_time_ms' => (int) round((microtime(true) - $bioStartedAt) * 1000),
            ]);
        }

        return KycBiometricCheck::create([
            'kyc_profile_id' => $profile->id,
            'kyc_document_id' => $document?->id,
            'selfie_file_path' => $selfiePath,
            'provider' => $bioResult['provider'],
            'liveness_passed' => $bioResult['liveness_passed'],
            'liveness_score' => $bioResult['liveness_score'],
            'face_match_passed' => $bioResult['face_match_passed'],
            'face_match_score' => $bioResult['face_match_score'],
            'biometric_meta' => $bioResult['biometric_meta'],
        ]);
    }

    /**
     * Record a manual or automated review decision.
     * Enforces the rule: OCR confidence > 0.85 DOES NOT automatically approve KYC!
     */
    public function recordDecision(
        KycProfile $profile,
        string $decision, // 'approved', 'rejected', 'info_requested'
        ?int $reviewerUserId = null,
        ?string $notes = null,
        ?array $requestedItems = null,
    ): KycReviewDecision {
        if (! in_array($decision, ['approved', 'rejected', 'info_requested'], true)) {
            throw ValidationException::withMessages([
                'decision' => 'Décision de revue KYC invalide.',
            ]);
        }

        // A blocked/suspended case can never be approved or rejected as a
        // regular review outcome: it must be lifted by compliance first.
        if ($profile->complianceCase?->isBlockedOrSuspended()) {
            throw ValidationException::withMessages([
                'kyc' => 'Ce dossier fait l\'objet d\'un blocage ou d\'une suspension : il doit être traité par le service conformité.',
            ]);
        }

        return DB::transaction(function () use ($profile, $decision, $reviewerUserId, $notes, $requestedItems) {
            $record = KycReviewDecision::create([
                'kyc_profile_id' => $profile->id,
                'reviewer_user_id' => $reviewerUserId,
                'decision' => $decision,
                'notes' => $notes,
                'requested_fields_or_docs' => $requestedItems,
            ]);

            if ($decision === 'approved') {
                $profile->status = KycStatus::Approved;
                $profile->reviewed_at = now();
                $profile->reviewed_by_user_id = $reviewerUserId;
                $profile->last_verified_at = now();
                $profile->valid_until = now()->addYear();
                $profile->rejection_reason = null;
                $profile->save();

                $profile->documents()->update([
                    'status' => 'verified',
                    'rejection_reason' => null,
                ]);

                $profile->latestVerification?->forceFill([
                    'status' => 'verified',
                    'requires_manual_review' => false,
                    'completed_at' => now(),
                ])->save();

                if ($profile->complianceCase) {
                    $profile->complianceCase->forceFill([
                        'status' => ComplianceCaseStatus::Approved,
                        'reviewed_at' => now(),
                        'assigned_to_user_id' => $reviewerUserId,
                    ])->save();
                }
            } elseif ($decision === 'rejected') {
                $profile->status = KycStatus::Rejected;
                $profile->reviewed_at = now();
                $profile->reviewed_by_user_id = $reviewerUserId;
                $profile->rejection_reason = $notes;
                $profile->save();

                $profile->documents()->update([
                    'status' => 'rejected',
                    'rejection_reason' => $notes,
                ]);

                $profile->latestVerification?->forceFill([
                    'status' => 'failed',
                    'requires_manual_review' => false,
                    'completed_at' => now(),
                ])->save();

                if ($profile->complianceCase) {
                    $profile->complianceCase->forceFill([
                        'status' => ComplianceCaseStatus::Rejected,
                        'reviewed_at' => now(),
                    ])->save();
                }
            } elseif ($decision === 'info_requested') {
                $profile->status = KycStatus::UnderReview;
                $profile->save();

                if ($profile->complianceCase) {
                    $profile->complianceCase->forceFill([
                        'status' => ComplianceCaseStatus::PendingDocuments,
                    ])->save();
                }
            }

            if ($profile->compliance_case_id !== null) {
                ComplianceAuditLog::create([
                    'compliance_case_id' => $profile->compliance_case_id,
                    'actor_user_id' => $reviewerUserId,
                    'event' => 'kyc.review_decision',
                    'subject_type' => $profile::class,
                    'subject_id' => $profile->id,
                    'context' => [
                        'decision' => $decision,
                        'notes' => $notes,
                        'requested_items' => $requestedItems,
                        'ocr_confidence_only' => true,
                    ],
                    'legal_retention_required' => true,
                    'retention_until' => now()->addYears(config('compliance.legal.retention_years', 10)),
                ]);
            }

            return $record;
        });
    }
}
