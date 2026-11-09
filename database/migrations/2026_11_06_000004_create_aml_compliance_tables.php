<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. PEP Screenings (Politically Exposed Persons & Relatives/Close Associates)
        Schema::create('pep_screenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compliance_case_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_name');
            $table->string('subject_country', 3)->default('CD');
            $table->string('provider')->default('mock');

            $table->boolean('has_match')->default(false);
            $table->string('match_type')->nullable(); // 'direct_pep', 'family_member', 'close_associate'
            $table->decimal('match_score', 5, 2)->default(0.00); // 0.00 - 1.00
            $table->string('pep_role_title')->nullable();
            $table->string('pep_jurisdiction')->nullable();
            $table->json('raw_matches')->nullable();

            $table->boolean('is_false_positive')->default(false);
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        // 2. Sanctions Screenings (UN, OFAC, EU, UK, CENAREF/RDC watchlists)
        Schema::create('sanctions_screenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compliance_case_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_name');
            $table->string('subject_type')->default('individual'); // 'individual' or 'entity'
            $table->string('provider')->default('mock');

            $table->boolean('has_match')->default(false);
            $table->decimal('match_score', 5, 2)->default(0.00);
            $table->json('matched_lists')->nullable(); // ['UN_CONSOLIDATED', 'OFAC_SDN', 'EU_SANCTIONS', 'CENAREF_RDC']
            $table->json('raw_matches')->nullable();

            $table->boolean('is_false_positive')->default(false);
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        // 3. Adverse Media Screenings (Reputational and judicial negative press)
        Schema::create('adverse_media_screenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compliance_case_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_name');
            $table->string('provider')->default('mock');

            $table->boolean('has_hits')->default(false);
            $table->unsignedInteger('hit_count')->default(0);
            $table->json('hits_details')->nullable(); // list of { title, source, snippet, date, category, risk }

            $table->boolean('is_reviewed')->default(false);
            $table->text('analyst_notes')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 4. Source of Funds Declarations (SOF — Origine précise des fonds de l'investissement)
        Schema::create('source_of_funds_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compliance_case_id')->nullable()->constrained()->nullOnDelete();
            // Retention: declarations must survive user deletion for 10 years.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('operation_reference')->nullable()->index(); // ID of Deal / Escrow

            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3)->default('USD');
            $table->string('funds_category'); // App\Enums\SourceOfFundsCategory
            $table->text('origin_description');

            $table->string('bank_name')->nullable();
            $table->string('bank_country', 3)->default('CD');
            $table->text('account_iban_or_number_encrypted')->nullable();

            $table->json('supporting_document_paths')->nullable(); // Bank statements, contracts, tax receipts
            $table->boolean('is_verified')->default(false);
            $table->text('verification_notes')->nullable();
            $table->timestamps();
        });

        // 5. Source of Wealth Declarations (SOW — Origine générale du patrimoine)
        Schema::create('source_of_wealth_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compliance_case_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedBigInteger('estimated_total_net_worth_minor')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->text('wealth_description'); // Overview of wealth accumulation history
            $table->json('wealth_components')->nullable(); // [{ type: 'business', pct: 60 }, { type: 'real_estate', pct: 40 }]
            $table->json('supporting_document_paths')->nullable();

            $table->boolean('is_verified')->default(false);
            $table->text('verification_notes')->nullable();
            $table->timestamps();
        });

        // 6. AML Risk Assessments (Comprehensive multi-pillar risk calculation)
        Schema::create('aml_risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compliance_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rule_version_id')->nullable()->constrained('compliance_rule_versions')->nullOnDelete();

            // Pillar sub-scores (0 - 100)
            $table->unsignedInteger('client_nature_score')->default(0);
            $table->unsignedInteger('ubo_complexity_score')->default(0);
            $table->unsignedInteger('geographic_score')->default(0);
            $table->unsignedInteger('sectoral_score')->default(0);
            $table->unsignedInteger('product_channel_score')->default(0);
            $table->unsignedInteger('source_of_funds_score')->default(0);
            $table->unsignedInteger('transaction_behavior_score')->default(0);

            // Calculated aggregate score and risk level
            $table->unsignedInteger('overall_score')->default(0); // 0 - 100
            $table->string('risk_level')->default('low'); // App\Enums\AmlRiskLevel

            $table->boolean('requires_enhanced_due_diligence')->default(false);
            $table->text('mitigating_factors')->nullable();
            $table->text('analyst_rationale')->nullable();

            $table->foreignId('assessed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assessed_at')->useCurrent();
            $table->timestamps();
        });

        // 7. AML Alerts (Compliance alerts with resolution workflow)
        Schema::create('aml_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compliance_case_id')->constrained()->cascadeOnDelete();
            $table->string('alert_type'); // App\Enums\AmlAlertType
            $table->string('severity')->default('medium'); // App\Enums\AmlRiskLevel
            $table->string('status')->default('open'); // App\Enums\AmlAlertStatus

            $table->string('source'); // 'system_rule', 'screening_provider', 'analyst', 'transaction_monitor'
            $table->text('title');
            $table->text('description');
            $table->json('metadata')->nullable();

            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('resolution_decision')->nullable(); // 'false_positive', 'confirmed_risk', 'dismissed', 'escalated_for_dos'
            $table->text('resolution_comment')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aml_alerts');
        Schema::dropIfExists('aml_risk_assessments');
        Schema::dropIfExists('source_of_wealth_declarations');
        Schema::dropIfExists('source_of_funds_declarations');
        Schema::dropIfExists('adverse_media_screenings');
        Schema::dropIfExists('sanctions_screenings');
        Schema::dropIfExists('pep_screenings');
    }
};
