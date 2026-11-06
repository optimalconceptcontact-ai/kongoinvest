<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bibliothèque structurée de clauses : décrites par
        // catégorie / instrument / forme juridique / juridiction,
        // jamais insérées systématiquement dans tous les contrats
        // (prompt §5). Seedée par rôle métier — l'IA recommande,
        // les parties décident.
        Schema::create('contract_clause_library', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('category'); // App\Enums\ClauseCategory

            // Applicabilité — tableaux vides = toutes les valeurs.
            $table->json('instruments')->nullable(); // App\Enums\FinancingComponentType[]
            $table->json('legal_forms')->nullable(); // App\Enums\LegalForm[]
            $table->json('jurisdictions')->nullable(); // ex. ["OHADA-RDC"]
            $table->json('affected_scopes')->nullable(); // sous-portée : minoritaire/fondateur-flexible... du payload recommandation

            $table->text('purpose');
            $table->text('plain_language_explanation'); // explication en langage simple
            $table->text('founder_benefit');
            $table->text('investor_benefit');
            $table->text('financial_impact');
            $table->text('legal_impact');
            $table->text('risks');

            // Modèle contractuel avec placeholders ({{...}}).
            $table->text('body');
            $table->unsignedSmallInteger('current_version')->default(1);
            $table->json('variants')->nullable(); // ex. antidilution : full_ratchet / wav_broad / wav_narrow / none

            // Références juridiques structurées (legal_references).
            $table->json('legal_bases')->nullable();
            $table->boolean('requires_legal_review')->default(false); // validation juridique humaine attendue
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        // Historique des versions de la bibliothèque — une
        // modification crée une nouvelle version, l'ancienne
        // reste consultable (traçabilité prompt §10).
        Schema::create('contract_clause_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_clause_library_id')->constrained('contract_clause_library')->cascadeOnDelete();
            $table->unsignedSmallInteger('version');

            $table->text('body');
            $table->json('variants')->nullable();
            $table->json('change_summary')->nullable();

            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['contract_clause_library_id', 'version']);
        });

        // Clause sélectionnée dans une opération. Le texte est un
        // instantané (un changement de texte crée une entrée
        // d'approbation / une révision — jamais une surcharge
        // silencieuse) ; la référence bibliothèque est nullable
        // pour les clauses personnalisées des parties.
        Schema::create('contract_clause_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_contract_workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_clause_library_id')->nullable()->constrained('contract_clause_library')->nullOnDelete();
            $table->unsignedSmallInteger('library_version')->nullable();
            $table->foreignId('snapshot_id')->nullable()->constrained('negotiation_conversation_snapshots')->nullOnDelete();

            $table->string('title');
            $table->string('category'); // App\Enums\ClauseCategory
            $table->string('origin'); // App\Enums\ClauseOrigin
            $table->string('status')->default('proposed_by_ai'); // App\Enums\ClauseStatus
            $table->string('instrument_scope')->nullable(); // App\Enums\FinancingComponentType

            // Texte contractuel courant + variante retenue.
            $table->text('body');
            $table->string('variant')->nullable();
            $table->text('rationale')->nullable();

            // Décisions des parties, dénormalisées pour l'affichage
            // ; la source de vérité reste contract_clause_approvals.
            $table->string('founder_decision')->nullable(); // App\Enums\ClauseDecision
            $table->string('investor_decision')->nullable(); // App\Enums\ClauseDecision
            $table->timestamp('founder_decided_at')->nullable();
            $table->timestamp('investor_decided_at')->nullable();

            $table->boolean('is_custom')->default(false);
            $table->foreignId('proposed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['investment_contract_workspace_id', 'status']);
        });

        // Acceptations, refus, contre-propositions : append-only,
        // avec texte de la contre-proposition et motif. Une clause
        // n'est acceptée qu'en présence de décisions accept de
        // chaque partie, toutes datées.
        Schema::create('contract_clause_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_clause_selection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('decision'); // App\Enums\ClauseDecision
            $table->text('body')->nullable(); // texte de la contre-proposition / révision
            $table->string('variant')->nullable();
            $table->text('comment')->nullable();

            $table->timestamp('decided_at');
            $table->timestamps();

            $table->index(['contract_clause_selection_id', 'decision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_clause_approvals');
        Schema::dropIfExists('contract_clause_selections');
        Schema::dropIfExists('contract_clause_versions');
        Schema::dropIfExists('contract_clause_library');
    }
};
