<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Instantanés versionnés de l'analyse des conversations
        // échangées dans l'espace de négociation (temps réel via
        // flux de messages de la DealRoom). Chaque instantané
        // référencant exactement les messages utilisés, chaque
        // information extraite reste vérifiable à sa source
        // (prompt §4) et le consentement applicable est celui
        // enregistré sur l'espace au moment de l'analyse.
        Schema::create('negotiation_conversation_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_contract_workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_negotiation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();

            $table->unsignedSmallInteger('version')->default(1);

            $table->string('status')->default('pending'); // App\Enums\AiRequestStatus
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('tokens_used')->nullable();
            $table->text('failure_reason')->nullable();

            // Périmètre exact utilisé : identifiants de messages,
            // bornes, nombre, consentements actifs au moment de
            // l'analyse (ex. {messages:[...], consent:{...}}).
            $table->json('coverage')->nullable();

            // Synthèse structurée des négociations produite par
            // l'IA, revalidée côté serveur avant écriture.
            $table->json('synthesis')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['investment_contract_workspace_id', 'version']);
        });

        // Conditions négociées extraites des conversations ou
        // saisies par les parties. Chaque condition garde un
        // statut (proposition initiale, contre-proposition,
        // contradictoire...) et une référence source vérifiable.
        Schema::create('negotiation_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_contract_workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('snapshot_id')->nullable()->constrained('negotiation_conversation_snapshots')->nullOnDelete();

            $table->string('subject'); // clé stable normalisée, ex. loan_amount, investor_share
            $table->string('label'); // libellé lisible
            $table->string('category'); // App\Enums\TermCategory
            $table->string('kind')->default('other'); // App\Enums\TermKind
            $table->string('instrument')->nullable(); // App\Enums\FinancingComponentType

            // Valeur normalisée (montants en unités mineures,
            // pourcentages en points de base, durées, libellés...).
            $table->json('value')->nullable();
            $table->string('display_value')->nullable(); // valeur telle que négociée, pour la partie

            $table->string('status')->default('under_discussion'); // App\Enums\NegotiationTermStatus
            $table->string('source')->default('manual'); // App\Enums\TermSource

            // Référence source vérifiable : message, auteur, date.
            $table->foreignId('source_message_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->json('source_context')->nullable(); // {author, posted_at, extract}

            $table->foreignId('proposed_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Détection de contradictions : lien vers la condition
            // — rivale, signalée mais jamais arbitrairement choisie.
            $table->foreignId('conflict_with_term_id')->nullable()->constrained('negotiation_terms')->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['investment_contract_workspace_id', 'subject']);
            $table->index(['investment_contract_workspace_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('negotiation_terms');
        Schema::dropIfExists('negotiation_conversation_snapshots');
    }
};
