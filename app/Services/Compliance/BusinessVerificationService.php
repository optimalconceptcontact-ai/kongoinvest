<?php

namespace App\Services\Compliance;

use App\Enums\BusinessLegalDocumentType;
use App\Enums\ComplianceCaseStatus;
use App\Enums\LegalForm;
use App\Models\BusinessLegalDocument;
use App\Models\BusinessVerification;
use App\Models\Company;
use App\Models\ComplianceAuditLog;
use App\Models\ComplianceCase;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BusinessVerificationService
{
    /**
     * Initialize or update a KYB dossier for a company.
     */
    public function initiateVerification(Company $company, User $applicant, array $data): BusinessVerification
    {
        return DB::transaction(function () use ($company, $applicant, $data) {
            // Find or create compliance case
            $complianceCase = ComplianceCase::firstOrCreate(
                [
                    'subject_type' => Company::class,
                    'subject_id' => $company->id,
                ],
                [
                    'case_number' => 'CASE-KYB-'.strtoupper(bin2hex(random_bytes(3))),
                    'user_id' => $applicant->id,
                    'status' => ComplianceCaseStatus::CollectingInformation,
                ]
            );

            // Create or update business verification record
            $verification = BusinessVerification::updateOrCreate(
                ['company_id' => $company->id],
                [
                    'compliance_case_id' => $complianceCase->id,
                    'company_name' => $data['company_name'] ?? $company->name,
                    'legal_form' => $data['legal_form'] ?? LegalForm::Sarl->value,
                    'rccm_number' => $data['rccm_number'] ?? $company->registration_number,
                    'tax_identification_number' => $data['tax_identification_number'] ?? null,
                    'national_identification_number' => $data['national_identification_number'] ?? null,
                    'registered_office_address' => $data['registered_office_address'] ?? null,
                    'country_of_incorporation' => $data['country_of_incorporation'] ?? 'CD',
                    'date_of_incorporation' => $data['date_of_incorporation'] ?? null,
                    'corporate_purpose' => $data['corporate_purpose'] ?? null,
                    'share_capital_minor' => $data['share_capital_minor'] ?? 0,
                    'share_capital_currency' => $data['share_capital_currency'] ?? 'USD',
                    'main_economic_activity' => $data['main_economic_activity'] ?? null,
                    'industry_sector' => $data['industry_sector'] ?? null,
                    'legal_representative_user_id' => $data['legal_representative_user_id'] ?? $applicant->id,
                    'legal_representative_name' => $data['legal_representative_name'] ?? $applicant->name,
                    'legal_representative_title' => $data['legal_representative_title'] ?? 'Gérant',
                    'has_sectorial_authorization' => (bool) ($data['has_sectorial_authorization'] ?? false),
                ]
            );

            // `status` is deliberately not mass-assignable, so it must be
            // written with forceFill() or updateOrCreate() silently drops it.
            $verification->forceFill([
                'status' => 'submitted',
            ])->save();

            $complianceCase->forceFill([
                'status' => ComplianceCaseStatus::AutomatedChecks,
                'submitted_at' => now(),
            ])->save();

            ComplianceAuditLog::create([
                'compliance_case_id' => $complianceCase->id,
                'actor_user_id' => $applicant->id,
                'event' => 'kyb.submitted',
                'subject_type' => $verification::class,
                'subject_id' => $verification->id,
                'context' => [
                    'company_name' => $verification->company_name,
                    'legal_form' => $verification->legal_form?->value,
                    'rccm_number' => $verification->rccm_number,
                ],
                'legal_retention_required' => true,
                'retention_until' => now()->addYears(config('compliance.legal.retention_years', 10)),
            ]);

            return $verification;
        });
    }

    /**
     * Upload and attach a legal document to the business verification.
     */
    public function attachDocument(
        BusinessVerification $verification,
        UploadedFile $file,
        BusinessLegalDocumentType $type,
        string $title,
        ?string $issueDate = null,
        ?string $expiryDate = null,
    ): BusinessLegalDocument {
        $hash = hash_file('sha256', $file->getRealPath());
        $path = $file->store('kyb-documents', 'private');

        $document = BusinessLegalDocument::create([
            'business_verification_id' => $verification->id,
            'document_type' => $type,
            'document_title' => $title,
            'file_path' => $path,
            'file_hash' => $hash,
            'issue_date' => $issueDate,
            'expiry_date' => $expiryDate,
            'is_verified' => false,
        ]);

        if ($verification->compliance_case_id !== null) {
            ComplianceAuditLog::create([
                'compliance_case_id' => $verification->compliance_case_id,
                'actor_user_id' => $verification->legal_representative_user_id,
                'event' => 'kyb.document_attached',
                'subject_type' => $document::class,
                'subject_id' => $document->id,
                'context' => [
                    'document_type' => $type->value,
                    'document_title' => $title,
                    'file_hash' => $hash,
                ],
                'legal_retention_required' => true,
                'retention_until' => now()->addYears(config('compliance.legal.retention_years', 10)),
            ]);
        }

        return $document;
    }

    /**
     * Validate business verification coherence across statutes, RCCM, NIF, and representatives.
     *
     * @return array{
     *     is_coherent: bool,
     *     missing_mandatory_docs: array<string>,
     *     warnings: array<string>
     * }
     */
    public function assessDocumentCoherence(BusinessVerification $verification): array
    {
        $docs = $verification->legalDocuments()->get()->pluck('document_type')->map(fn ($dt) => $dt->value)->all();

        $mandatory = [
            BusinessLegalDocumentType::Rccm->value,
            BusinessLegalDocumentType::Statutes->value,
            BusinessLegalDocumentType::Nif->value,
        ];

        $missing = array_diff($mandatory, $docs);
        $warnings = [];

        // OHADA Specific validation
        $form = $verification->legal_form;
        if (in_array($form, [LegalForm::Sarlu, LegalForm::Sasu]) && $verification->beneficialOwners()->count() > 1) {
            $warnings[] = "La forme sociale déclarée est unipersonnelle ({$form->value}), mais plusieurs bénéficiaires effectifs sont déclarés.";
        }

        if (empty($verification->rccm_number)) {
            $warnings[] = 'Numéro RCCM manquant pour une entreprise constituée.';
        }

        return [
            'is_coherent' => empty($missing) && empty($warnings),
            'missing_mandatory_docs' => array_values($missing),
            'warnings' => $warnings,
        ];
    }

    /**
     * Approve or reject business verification by compliance officer.
     */
    public function reviewVerification(
        BusinessVerification $verification,
        string $status, // 'verified', 'rejected'
        User $reviewer,
        ?string $notes = null
    ): BusinessVerification {
        if (! in_array($status, ['verified', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Statut de revue KYB invalide.',
            ]);
        }

        $case = $verification->complianceCase;

        if ($case?->isBlockedOrSuspended()) {
            throw ValidationException::withMessages([
                'verification' => 'Ce dossier KYB est bloqué ou suspendu : il ne peut pas être revu avant levée de la mesure par le service conformité.',
            ]);
        }

        return DB::transaction(function () use ($verification, $status, $reviewer, $notes, $case) {
            $verification->status = $status;
            $verification->verification_notes = $notes;
            $verification->verified_at = $status === 'verified' ? now() : null;
            $verification->save();

            if ($case) {
                $case->forceFill([
                    'status' => $status === 'verified' ? ComplianceCaseStatus::Approved : ComplianceCaseStatus::Rejected,
                    'reviewed_at' => now(),
                    'assigned_to_user_id' => $reviewer->id,
                ])->save();

                ComplianceAuditLog::create([
                    'compliance_case_id' => $case->id,
                    'actor_user_id' => $reviewer->id,
                    'event' => 'kyb.review_decision',
                    'subject_type' => $verification::class,
                    'subject_id' => $verification->id,
                    'context' => [
                        'decision' => $status,
                        'notes' => $notes,
                    ],
                    'legal_retention_required' => true,
                    'retention_until' => now()->addYears(config('compliance.legal.retention_years', 10)),
                ]);
            }

            return $verification;
        });
    }
}
