<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Orthogonal compliance & maturity statuses on projects table
        Schema::table('projects', function (Blueprint $table) {
            $table->string('maturity_stage')->default('idea')->after('status'); // App\Enums\ProjectMaturityStage
            $table->string('identity_status')->default('unverified')->after('maturity_stage');
            $table->string('business_status')->default('not_incorporated')->after('identity_status'); // App\Enums\BusinessStatus
            $table->string('compliance_status')->default('not_started')->after('business_status');
            $table->foreignId('compliance_case_id')->nullable()->after('compliance_status')->constrained('compliance_cases')->nullOnDelete();
        });

        // 2. Project Formation Readiness (Section 19: Pre-incorporation & Company structuring)
        Schema::create('project_formation_readiness', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('intended_legal_form')->nullable(); // sarlu, sarl, sasu, sas, sa
            $table->string('intended_company_name')->nullable();
            $table->text('intended_corporate_purpose')->nullable();
            $table->string('intended_registered_office')->nullable();
            $table->unsignedBigInteger('intended_capital_minor')->default(0);
            $table->string('intended_capital_currency', 3)->default('USD');

            // Indicative cap table of co-founders (NEVER legally definitive until registered)
            $table->json('indicative_cofounders')->nullable();
            // shape: [
            //   { "name": "...", "email": "...", "phone": "...", "role": "...", "projected_share_bps": 3000, "is_lead": true, "kyc_profile_id": 12 }
            // ]

            // System recommendation
            $table->string('recommended_legal_form')->nullable();
            $table->text('recommendation_rationale')->nullable();
            $table->unsignedInteger('readiness_score')->default(0); // 0 - 100
            $table->json('readiness_checklist')->nullable();

            // Transition tracking when business is finally incorporated
            $table->foreignId('converted_to_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_formation_readiness');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['compliance_case_id']);
            $table->dropColumn([
                'maturity_stage',
                'identity_status',
                'business_status',
                'compliance_status',
                'compliance_case_id',
            ]);
        });
    }
};
