<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Conditions suspensives / résolutives, obligations et
        // engagements (covenants). Une condition bloque la clôture
        // tant qu'elle n'est pas conduite à terme, levée ou
        // explicitement renoncée.
        Schema::create('contract_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_contract_workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_document_id')->nullable()->constrained()->nullOnDelete();

            $table->string('type'); // App\Enums\ContractConditionType
            $table->string('responsible_party')->default('mutual'); // App\Enums\ContractPartyRole
            $table->text('description');
            $table->date('due_date')->nullable(); // null = aucune échéance : le moteur de contrôle déclenchera une alerte
            $table->string('status')->default('pending'); // App\Enums\ContractConditionStatus

            $table->json('source')->nullable(); // clause(s) / condition(s) négociée(s) à l'origine
            $table->timestamp('fulfilled_at')->nullable();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['investment_contract_workspace_id', 'type', 'status']);
        });

        // Signatures — la signature est la preuve du prestataire ou
        // le dépôt des exemplaires signés, jamais une validation
        // d'interface (prompt §11). Chaque ligne pointe la version
        // exacte signée avec son empreinte.
        Schema::create('contract_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_contract_workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_document_version_id')->constrained()->cascadeOnDelete();

            $table->foreignId('signer_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('signer_role'); // App\Enums\ContractPartyRole

            $table->string('method'); // App\Enums\SignatureMethod
            $table->string('status')->default('requested'); // App\Enums\SignatureStatus

            $table->string('provider')->nullable();
            $table->string('provider_reference')->nullable();
            $table->json('provider_metadata')->nullable();
            $table->json('signature_evidence')->nullable(); // preuves : fichier signé, identifiant d'enveloppe, attestations

            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('signed_at')->nullable();

            $table->timestamps();

            $table->index(['investment_contract_workspace_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_signatures');
        Schema::dropIfExists('contract_conditions');
    }
};
