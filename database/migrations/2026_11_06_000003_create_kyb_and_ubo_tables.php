<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Business Verifications (KYB for companies & target companies)
        Schema::create('business_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('compliance_case_id')->nullable()->constrained()->nullOnDelete();

            $table->string('company_name');
            $table->string('legal_form')->default('sarl'); // OHADA: sarl, sarlu, sa, sas, sasu...
            $table->string('rccm_number')->nullable()->index();
            $table->string('tax_identification_number')->nullable()->index(); // NIF
            $table->string('national_identification_number')->nullable()->index(); // Id. Nat.

            $table->text('registered_office_address')->nullable();
            $table->string('country_of_incorporation', 3)->default('CD');
            $table->date('date_of_incorporation')->nullable();
            $table->text('corporate_purpose')->nullable();

            $table->unsignedBigInteger('share_capital_minor')->default(0);
            $table->string('share_capital_currency', 3)->default('USD');
            $table->string('main_economic_activity')->nullable();
            $table->string('industry_sector')->nullable();

            $table->foreignId('legal_representative_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('legal_representative_name')->nullable();
            $table->string('legal_representative_title')->nullable(); // Gérant, DG, Président

            $table->string('status')->default('draft'); // draft, submitted, under_review, verified, rejected
            $table->boolean('has_sectorial_authorization')->default(false);
            $table->text('verification_notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Business Legal Documents (Statuts, RCCM, NIF, PV, etc.)
        Schema::create('business_legal_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_verification_id')->constrained()->cascadeOnDelete();
            $table->string('document_type'); // App\Enums\BusinessLegalDocumentType
            $table->string('document_title');
            $table->string('file_path');
            $table->string('file_hash')->index();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->text('verification_notes')->nullable();
            $table->timestamps();
        });

        // 3. Beneficial Owners (Physical persons qualifying as UBO)
        Schema::create('beneficial_owners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_verification_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // If registered on platform

            $table->string('first_name');
            $table->string('last_name');
            $table->string('postnom')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationality', 3)->default('CD');
            $table->string('country_of_residence', 3)->default('CD');
            $table->string('residential_address')->nullable();

            $table->boolean('is_pep')->default(false);
            $table->string('pep_details')->nullable();

            // Ownership metrics
            $table->unsignedInteger('direct_ownership_bps')->default(0); // in basis points: 2500 = 25%
            $table->unsignedInteger('indirect_ownership_bps')->default(0);
            $table->unsignedInteger('voting_rights_bps')->default(0);
            $table->string('control_nature'); // App\Enums\UboControlNature

            $table->boolean('meets_statutory_threshold')->default(false); // >= threshold (ex. 25% OHADA or lower if high-risk)
            $table->text('justification_notes')->nullable();
            $table->string('identity_document_file_path')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });

        // 4. Ownership Relationships (Edges in the multi-tier holding structure)
        // e.g. Person A -> Holding B -> Holding C -> Target Company
        Schema::create('ownership_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_verification_id')->constrained()->cascadeOnDelete();

            $table->string('parent_entity_type'); // 'individual' or 'legal_entity'
            $table->string('parent_name');
            $table->string('parent_registration_number')->nullable(); // RCCM or ID number
            $table->string('parent_country', 3)->default('CD');

            $table->string('child_entity_name');
            $table->string('child_registration_number')->nullable();

            $table->unsignedInteger('tier_level')->default(1); // 1 = direct to target, 2 = holding intermediate, 3 = parent...
            $table->unsignedInteger('equity_percentage_bps')->default(0); // e.g. 5000 = 50.00%
            $table->unsignedInteger('voting_percentage_bps')->default(0);
            $table->string('control_mechanism')->default('direct_equity'); // App\Enums\UboControlNature

            $table->boolean('is_ultimate_owner')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ownership_relationships');
        Schema::dropIfExists('beneficial_owners');
        Schema::dropIfExists('business_legal_documents');
        Schema::dropIfExists('business_verifications');
    }
};
