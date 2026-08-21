<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('generated_by_user_id')->constrained('users')->cascadeOnDelete();

            $table->string('status')->default('generated'); // App\Enums\ContractStatus
            $table->string('investment_type'); // App\Enums\FundingType (equity, debt, ...)

            // Money — minor units + explicit currency, never float (§146).
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3)->default('USD');

            // Structured terms feeding the generated document — see
            // spec §128 (parties, montant, devise, date, type, clauses).
            $table->json('terms')->nullable();

            $table->string('template_version')->default('v1');

            // Both timestamps null = pending; both set = fully accepted.
            // Which user is "the entrepreneur" / "the investor" is
            // already unambiguous via the parent deal_room's
            // entrepreneur_user_id / investor_user_id — no need to
            // duplicate that here.
            $table->timestamp('entrepreneur_accepted_at')->nullable();
            $table->timestamp('investor_accepted_at')->nullable();

            $table->timestamps();
        });

        Schema::create('contract_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            // Immutable snapshot of the full contract content at the time
            // this version was generated/accepted (spec §128 — "Conserver
            // une copie immuable de la version acceptée"). Never updated
            // once written; a change creates a new version + new row.
            $table->json('content');
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['contract_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_versions');
        Schema::dropIfExists('contracts');
    }
};
