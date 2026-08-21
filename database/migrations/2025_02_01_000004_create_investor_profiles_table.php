<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();

            // Spec §16: investors may be based anywhere. Nullable because
            // an empty profile is created at registration (spec §7) and
            // filled in during onboarding — `onboarding_completed` is
            // what downstream code checks, not these individually.
            $table->string('country_code', 2)->nullable();
            $table->string('city')->nullable();

            $table->string('investor_type')->nullable(); // App\Enums\InvestorType
            $table->string('funding_type')->nullable(); // App\Enums\FundingType
            $table->string('investment_horizon')->nullable(); // App\Enums\InvestmentHorizon
            $table->string('risk_appetite')->nullable(); // App\Enums\RiskAppetite

            // Ticket size in minor currency units (spec §146 — never
            // float for money). Currency is explicit since investors are
            // international (spec §126).
            $table->unsignedBigInteger('ticket_min_minor')->nullable();
            $table->unsignedBigInteger('ticket_max_minor')->nullable();
            $table->string('ticket_currency', 3)->default('USD');

            // ISO country codes the investor is willing to fund. A real
            // countries table isn't needed for this — it's a preference
            // filter, not a relation with its own attributes.
            $table->json('preferred_geographies')->nullable();

            $table->text('investment_thesis')->nullable();
            $table->boolean('impact_focused')->default(false);
            $table->boolean('onboarding_completed')->default(false);

            $table->timestamps();

            $table->index('country_code');
            $table->index('investor_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_profiles');
    }
};
