<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Structure juridique détaillée de l'entreprise : forme
        // sociale, RCCM, capital, représentant légal, statuts en
        // vigueur. Complète Company (qui garde une simple chaîne
        // legal_form libre) ; les règles de génération du module
        // dépendent de CETTE donnée structurée + son niveau de
        // vérification, jamais de la chaîne libre seule.
        Schema::create('company_legal_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('legal_form')->default('unknown'); // App\Enums\LegalForm
            $table->string('rccm')->nullable()->index();
            $table->string('registered_office')->nullable();
            $table->text('corporate_purpose')->nullable();

            // Capital social — montants en unités mineures + devise
            // explicite, convention plateforme (jamais de float).
            $table->unsignedBigInteger('capital_minor')->nullable();
            $table->string('capital_currency', 3)->default('USD');
            $table->unsignedBigInteger('share_count')->nullable();
            $table->unsignedBigInteger('nominal_value_minor')->nullable();

            // Représentation légale. L'utilisateur lié est nullable :
            // le représentant peut ne pas être un compte de la plateforme.
            $table->foreignId('representative_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('representative_name')->nullable();
            $table->string('representative_role')->nullable(); // gérant, président du CA, président SAS...

            $table->json('beneficial_owners')->nullable(); // bénéficiaires effectifs
            $table->text('statutory_restrictions')->nullable(); // restrictions statutaires
            $table->json('sectorial_authorizations')->nullable(); // autorisations administratives / sectorielles

            // Statuts en vigueur (version / date de référence).
            $table->string('statutes_version')->nullable();
            $table->date('statutes_date')->nullable();

            $table->string('verification')->default('unverified'); // App\Enums\DataVerificationStatus
            $table->text('verification_notes')->nullable();

            $table->boolean('is_current')->default(true);

            $table->timestamps();
        });

        // Associés / actionnaires et répartition du capital. La
        // distinction unipersonnelle / pluripersonnelle se lit ICI
        // (count des associés), pas dans un cas d'enum — SARLU/SASU
        // ne sont que des libellés de présentation (prompt §3.A).
        Schema::create('company_shareholdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('holder_type'); // App\Enums\ShareholderType
            $table->foreignId('holder_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('holder_name');

            $table->unsignedBigInteger('share_count')->nullable();
            // Pourcentages stockés en points de base (2 500 = 25 %) :
            // précision entière, aucune multiplication flottante.
            $table->unsignedInteger('percentage_bps')->nullable();

            $table->boolean('is_founder')->default(false);
            $table->boolean('is_beneficial_owner')->default(false);
            $table->string('verification')->default('unverified'); // App\Enums\DataVerificationStatus
            $table->text('notes')->nullable();

            $table->timestamps();
        });

        Schema::create('legal_references', function (Blueprint $table) {
            $table->id();

            $table->string('jurisdiction')->default('OHADA-RDC'); // architecture ouverte à d'autres juridictions
            $table->string('act_title'); // ex. AUSCGIE, AUPS...
            $table->string('article')->nullable();
            $table->string('version')->nullable(); // version / révision du texte
            $table->date('effective_from')->nullable();
            $table->string('source_url')->nullable();

            $table->text('summary');
            $table->json('keywords')->nullable();
            $table->timestamp('verified_at')->nullable(); // vérification humaine d'une source

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_references');
        Schema::dropIfExists('company_shareholdings');
        Schema::dropIfExists('company_legal_records');
    }
};
