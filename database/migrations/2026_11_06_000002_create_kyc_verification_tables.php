<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Upgrade kyc_profiles with detailed Congolese & international KYC fields
        Schema::table('kyc_profiles', function (Blueprint $table) {
            $table->foreignId('compliance_case_id')->nullable()->after('user_id')->constrained('compliance_cases')->nullOnDelete();

            $table->string('first_name')->nullable()->after('compliance_case_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('postnom')->nullable()->after('last_name'); // Spécificité état-civil RDC
            $table->date('date_of_birth')->nullable()->after('postnom');
            $table->string('place_of_birth')->nullable()->after('date_of_birth');
            $table->json('nationalities')->nullable()->after('place_of_birth'); // e.g. ["CD", "BE"]

            $table->string('residential_address')->nullable()->after('nationalities');
            $table->string('residential_country', 3)->default('CD')->after('residential_address');
            $table->string('phone')->nullable()->after('residential_country');
            $table->string('email')->nullable()->after('phone');

            $table->string('profession')->nullable()->after('email');
            $table->string('employer_or_activity')->nullable()->after('profession');

            // PEP status declaration
            $table->boolean('is_pep')->default(false)->after('employer_or_activity');
            $table->string('pep_function')->nullable()->after('is_pep');
            $table->date('pep_start_date')->nullable()->after('pep_function');
            $table->date('pep_end_date')->nullable()->after('pep_start_date');

            // Acting for third party
            $table->boolean('is_acting_for_third_party')->default(false)->after('pep_end_date');
            $table->text('third_party_details')->nullable()->after('is_acting_for_third_party');

            $table->string('risk_level')->default('low')->after('third_party_details');
            $table->timestamp('last_verified_at')->nullable()->after('reviewed_at');
            $table->timestamp('valid_until')->nullable()->after('last_verified_at');
        });

        // 2. KYC Verifications (Reusable verification sessions across investment operations)
        Schema::create('kyc_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kyc_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('compliance_case_id')->nullable()->constrained()->nullOnDelete();
            $table->string('operation_reference')->nullable()->index(); // ID of Deal / Workspace if specific
            $table->string('status')->default('pending'); // pending, processing, verified, failed, expired
            $table->unsignedInteger('overall_confidence_score')->default(0); // 0-100
            $table->boolean('requires_manual_review')->default(true);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // 3. KYC Documents (Individual identity documents)
        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kyc_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kyc_verification_id')->nullable()->constrained()->nullOnDelete();

            $table->string('document_type'); // App\Enums\DocumentType
            $table->string('issuing_country', 3)->default('CD');
            $table->text('document_number_encrypted'); // Encrypted at rest
            $table->string('document_number_hash')->index(); // Blind index for duplicate detection
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable(); // Nullable for DRC voter card

            // File references (stored in private disk)
            $table->string('front_file_path');
            $table->string('back_file_path')->nullable();
            $table->string('file_hash')->index(); // SHA256 of uploaded file to detect duplicates

            $table->string('status')->default('uploaded'); // uploaded, processing, verified, rejected
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        // 4. KYC Document Checks (OCR & Validation Checks per document)
        Schema::create('kyc_document_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kyc_document_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('mock');

            $table->boolean('is_readable')->default(true);
            $table->boolean('is_expired')->default(false);
            $table->boolean('is_duplicate')->default(false);
            $table->boolean('tampering_detected')->default(false);
            $table->boolean('data_matches_form')->default(true);

            // OCR extraction result and separate confidence scores
            $table->json('extracted_fields')->nullable();
            $table->json('field_confidence_scores')->nullable(); // { "first_name": 0.95, "last_name": 0.88, ... }
            $table->decimal('overall_ocr_score', 5, 2)->default(0.00); // Ex: 0.91 (NOTE: score > 0.85 DOES NOT auto-approve)

            $table->json('inconsistencies_detected')->nullable();
            $table->text('check_summary')->nullable();
            $table->timestamps();
        });

        // 5. KYC Biometric Checks (Selfie vs Document and Liveness)
        Schema::create('kyc_biometric_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kyc_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kyc_document_id')->nullable()->constrained()->nullOnDelete();

            $table->string('selfie_file_path');
            $table->string('provider')->default('mock');
            $table->boolean('liveness_passed')->default(false);
            $table->decimal('liveness_score', 5, 2)->default(0.00); // 0.00 - 1.00
            $table->boolean('face_match_passed')->default(false);
            $table->decimal('face_match_score', 5, 2)->default(0.00); // 0.00 - 1.00
            $table->json('biometric_meta')->nullable();
            $table->timestamps();
        });

        // 6. KYC Review Decisions
        Schema::create('kyc_review_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kyc_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision'); // auto_verified, manual_approved, info_requested, rejected
            $table->text('notes')->nullable();
            $table->json('requested_fields_or_docs')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_review_decisions');
        Schema::dropIfExists('kyc_biometric_checks');
        Schema::dropIfExists('kyc_document_checks');
        Schema::dropIfExists('kyc_documents');
        Schema::dropIfExists('kyc_verifications');

        Schema::table('kyc_profiles', function (Blueprint $table) {
            $table->dropForeign(['compliance_case_id']);
            $table->dropColumn([
                'compliance_case_id', 'first_name', 'last_name', 'postnom',
                'date_of_birth', 'place_of_birth', 'nationalities',
                'residential_address', 'residential_country', 'phone', 'email',
                'profession', 'employer_or_activity', 'is_pep', 'pep_function',
                'pep_start_date', 'pep_end_date', 'is_acting_for_third_party',
                'third_party_details', 'risk_level', 'last_verified_at', 'valid_until',
            ]);
        });
    }
};
