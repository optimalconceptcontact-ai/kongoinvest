<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Espace contractuel d'une opération d'investissement —
        // un par DealRoom : une proposition acceptée = une
        // opération, une entreprise peut avoir plusieurs opérations
        // avec chacune ses conditions et ses documents (prompt §2).
        Schema::create('investment_contract_workspaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_room_id')->unique()->constrained()->cascadeOnDelete();
            // Dénormalisé pour lister les opérations d'une entreprise
            // sans traverser connection -> project -> profile.
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();

            $table->string('status')->default('draft'); // App\Enums\WorkspaceStatus

            // Nature de l'opération — déduite des composantes, jamais
            // supposée ; une opération mixte garde capital et dette
            // distincts aussi bien financièrement que juridiquement.
            $table->string('financing_category')->nullable(); // App\Enums\FinancingCategory
            $table->string('founder_control_preference')->nullable(); // App\Enums\FounderControlPreference
            $table->text('founder_position_notes')->nullable();

            // Position structurée de l'investisseur (prompt §3.D).
            $table->json('investor_position')->nullable();
            $table->text('investor_position_notes')->nullable();

            // Montants — unités mineures + devise explicite.
            $table->unsignedBigInteger('pre_money_minor')->nullable();
            $table->unsignedBigInteger('post_money_minor')->nullable();
            $table->unsignedBigInteger('total_investment_minor')->nullable();
            $table->string('currency', 3)->default('USD');

            // Participations en points de base (2 500 = 25 %).
            $table->unsignedInteger('founder_share_bps')->nullable();
            $table->unsignedInteger('investor_share_bps')->nullable();

            // Projection de la table de capitalisation après
            // opération, calculée par CapTableCalculationService.
            $table->json('post_operation_cap_table')->nullable();

            // Consentement à l'analyse des conversations (prompt §14).
            $table->timestamp('entrepreneur_analysis_consent_at')->nullable();
            $table->timestamp('investor_analysis_consent_at')->nullable();

            // Validation juridique finale — distincte de la validation
            // commerciale des parties (prompt §16, règle 15).
            $table->timestamp('legal_reviewed_at')->nullable();
            $table->foreignId('legal_verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('closed_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();

            $table->timestamps();
        });

        // Rondes de négociation : informations générales et état
        // d'avancement (prompt — ContractNegotiation).
        Schema::create('contract_negotiations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_contract_workspace_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('round')->default(1);

            $table->string('title')->nullable();
            $table->string('status')->default('open'); // App\Enums\NegotiationStatus
            $table->text('summary')->nullable();
            // Conditions commerciales agrées de cette ronde — jamais
            // assimilées à des obligations juridiques sans document.
            $table->json('agreed_conditions')->nullable();

            $table->timestamp('opened_at')->nullable();
            $table->timestamp('concluded_at')->nullable();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['investment_contract_workspace_id', 'round']);
        });

        // Composantes financières de l'opération. Une même personne
        // peut être simultanément actionnaire et créancière ; chaque
        // composante est documentée et contractualisée séparément.
        Schema::create('contract_financing_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_contract_workspace_id')->constrained()->cascadeOnDelete();

            $table->string('type'); // App\Enums\FinancingComponentType
            $table->string('equity_mechanism')->nullable(); // App\Enums\EquityMechanism (composante capital uniquement)
            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->unsignedBigInteger('amount_minor')->nullable();
            $table->string('currency', 3)->default('USD');

            $table->string('status')->default('under_discussion'); // App\Enums\NegotiationTermStatus
            $table->json('terms')->nullable(); // modalités propres : taux, échéancier, garanties, ratchet...

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_financing_components');
        Schema::dropIfExists('contract_negotiations');
        Schema::dropIfExists('investment_contract_workspaces');
    }
};
