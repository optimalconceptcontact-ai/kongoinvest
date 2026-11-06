<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Contrôles de conformité juridique (LegalCheckType) et de
        // cohérence financière (FinancialCheckType) : déterministes,
        // avec sévérité et explication compréhensible (prompt §10).
        // Deux tables dédiées — entités distinctes exigées, mais
        // même mécanique batch harness exécuable en même fois.
        Schema::create('legal_compliance_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_contract_workspace_id')->constrained()->cascadeOnDelete();

            // Identifiant d'exécution regroupant les contrôles d'une
            // même passe (une relance remplace les précédents qui
            // restent consultables).
            $table->string('batch')->index();
            $table->string('type'); // App\Enums\LegalCheckType
            $table->string('severity')->default('medium'); // App\Enums\CheckSeverity
            $table->string('status')->default('passed'); // App\Enums\CheckStatus

            $table->string('title');
            $table->text('message'); // explication compréhensible
            $table->json('details')->nullable();
            $table->json('references')->nullable(); // legal_references / sources convoquées

            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index(['investment_contract_workspace_id', 'batch', 'status']);
        });

        Schema::create('financial_consistency_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_contract_workspace_id')->constrained()->cascadeOnDelete();

            $table->string('batch')->index();
            $table->string('type'); // App\Enums\FinancialCheckType
            $table->string('severity')->default('medium'); // App\Enums\CheckSeverity
            $table->string('status')->default('passed'); // App\Enums\CheckStatus

            $table->string('title');
            $table->text('message');
            $table->json('details')->nullable(); // valeurs calculées : attendu / constaté
            $table->json('references')->nullable(); // terms / composantes / documents

            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index(['investment_contract_workspace_id', 'batch', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_consistency_checks');
        Schema::dropIfExists('legal_compliance_checks');
    }
};
