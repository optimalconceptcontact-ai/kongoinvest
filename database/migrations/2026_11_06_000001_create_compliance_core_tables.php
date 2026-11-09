<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Compliance Rule Versions (Versioned risk weights and thresholds)
        Schema::create('compliance_rule_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique(); // e.g. '1.0.0'
            $table->text('description')->nullable();
            $table->json('weights'); // e.g. {'client_nature': 20, ...}
            $table->json('thresholds'); // e.g. {'low': 25, 'medium': 50, ...}
            $table->boolean('is_active')->default(false);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Central Compliance Cases
        Schema::create('compliance_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->unique(); // e.g. 'CASE-2026-0001'
            $table->string('subject_type'); // User, Company, Project, InvestmentContractWorkspace
            $table->unsignedBigInteger('subject_id');
            $table->index(['subject_type', 'subject_id']);

            // Retention: a compliance case must survive user deletion for
            // the 10-year legal retention period (Loi 22/068, art. 33).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Case owner / applicant
            $table->string('status')->default('not_started'); // App\Enums\ComplianceCaseStatus
            $table->string('risk_level')->default('low'); // App\Enums\AmlRiskLevel
            $table->unsignedInteger('risk_score')->default(0); // 0-100 score

            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete(); // Compliance officer
            $table->foreignId('rule_version_id')->nullable()->constrained('compliance_rule_versions')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->text('suspension_or_block_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Compliance Case Actions (Timeline of activities on a case)
        Schema::create('compliance_case_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compliance_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action_type'); // status_change, document_requested, alert_created, note_added, review...
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('comment')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        // 4. Compliance Approvals (Formal approval/conditional sign-off)
        Schema::create('compliance_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compliance_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision'); // approved, approved_with_conditions, rejected
            $table->text('conditions')->nullable();
            $table->text('rationale');
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();
        });

        // 5. Compliance Provider Checks (External API calls audit & idempotence)
        Schema::create('compliance_provider_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compliance_case_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider'); // 'mock', 'smile_id', 'sumsub', 'comply_advantage', 'ceni'
            $table->string('check_type'); // 'ocr', 'biometric', 'aml_screening', 'pep', 'sanctions'
            $table->string('reference_id')->nullable()->index(); // External check transaction ID
            $table->string('status'); // pending, completed, failed
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->unsignedInteger('execution_time_ms')->nullable();
            $table->timestamps();
        });

        // 6. Compliance Audit Logs (Immutable compliance logs, subject to 10-year Congolese retention)
        Schema::create('compliance_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compliance_case_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event'); // e.g. 'case.created', 'dos.drafted', 'document.decrypted'
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->index(['subject_type', 'subject_id']);
            $table->json('context')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->boolean('legal_retention_required')->default(true); // Article 33 Loi 22/068: 10 ans
            $table->timestamp('retention_until')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_audit_logs');
        Schema::dropIfExists('compliance_provider_checks');
        Schema::dropIfExists('compliance_approvals');
        Schema::dropIfExists('compliance_case_actions');
        Schema::dropIfExists('compliance_cases');
        Schema::dropIfExists('compliance_rule_versions');
    }
};
