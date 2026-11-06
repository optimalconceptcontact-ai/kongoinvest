<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Documents contractuels générés ou déposés par opération.
        // Un document inutile n'est jamais généré ; les listes du
        // prompt §9 sont typées via ContractDocumentType seedées.
        Schema::create('contract_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_contract_workspace_id')->constrained()->cascadeOnDelete();

            $table->string('type'); // App\Enums\ContractDocumentType
            $table->string('title');
            $table->string('status')->default('draft'); // App\Enums\ContractDocumentStatus
            $table->string('confidentiality')->default('shared'); // App\Enums\DocumentConfidentiality

            $table->unsignedSmallInteger('current_version')->nullable();
            $table->unsignedBigInteger('source_component_id')->nullable(); // contract_financing_components lié

            // Contexte de génération : conditions / clauses /
            // instantanés utilisés pour produire ce document.
            $table->json('generation_context')->nullable();
            $table->string('generator_provider')->nullable();

            // Double circuit de validation (prompt §16, règle 15) :
            // validation commerciale par partie puis validation
            // juridique identifiable séparément.
            $table->timestamp('founder_approved_at')->nullable();
            $table->timestamp('investor_approved_at')->nullable();
            $table->timestamp('legal_validated_at')->nullable();
            $table->foreignId('legal_validated_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Exemplaire signé déposé (signature externe).
            $table->timestamp('signed_deposited_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['investment_contract_workspace_id', 'status']);
            $table->index(['investment_contract_workspace_id', 'type']);
        });

        // Versions documentaires : toute modification importante
        // crée une nouvelle version ; les versions ainsi que
        // l'empreinte numérique du document restent inaltérables
        // (prompt §11 — traçabilité des validations).
        Schema::create('contract_document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_document_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version');

            $table->text('title')->nullable();
            // Contenu structuré (zendettes, sections, échelonnement)
            // permettant la régénération PDF / DOCX. Contrat pool :
            // TOUJOURS plus flexible que du format HTML « mort ».
            $table->json('content');
            $table->longText('rendered_body')->nullable(); // rendu prêt pour PDF / DOCX
            $table->char('hash', 64); // empreinte SHA-256 du contenu exact

            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('generation_meta')->nullable(); // provider, modèle, références entrantes

            $table->timestamps();

            $table->unique(['contract_document_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_document_versions');
        Schema::dropIfExists('contract_documents');
    }
};
