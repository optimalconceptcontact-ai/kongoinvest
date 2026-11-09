<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financing_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('financial_instruments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('instrument_category')->index(); // equity, debt, hybrid, grant, revenue_share, alternative
            $table->boolean('repayment_required')->default(false);
            $table->boolean('ownership_dilution')->default(false);
            $table->string('remuneration_type')->default('none'); // fixed, variable, profit_share, none
            $table->boolean('conversion_possible')->default(false);
            $table->boolean('collateral_possible')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('financing_type_instrument', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financing_type_id')->constrained('financing_types')->cascadeOnDelete();
            $table->foreignId('financial_instrument_id')->constrained('financial_instruments')->cascadeOnDelete();
            $table->boolean('is_primary')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['financing_type_id', 'financial_instrument_id'], 'financing_type_inst_unique');
        });

        Schema::create('financing_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('source_category')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('financing_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('requires_intermediary')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('instrument_jurisdiction_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_instrument_id')->constrained('financial_instruments')->cascadeOnDelete();
            $table->string('jurisdiction_code', 10)->default('CD')->index(); // CD (DRC/OHADA), GLOBAL, etc.
            $table->string('legal_status', 20)->default('approved')->index(); // approved, restricted, under_review, unavailable, not_reviewed
            $table->boolean('requires_incorporation')->default(true);
            $table->unsignedBigInteger('minimum_capital_minor')->nullable();
            $table->text('regulatory_notes')->nullable();
            $table->json('compliance_requirements')->nullable();
            $table->timestamps();

            $table->unique(['financial_instrument_id', 'jurisdiction_code'], 'inst_jurisdiction_unique');
        });

        Schema::create('instrument_stage_compatibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_instrument_id')->constrained('financial_instruments')->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained('stages')->cascadeOnDelete();
            $table->boolean('is_recommended')->default(false);
            $table->timestamps();

            $table->unique(['financial_instrument_id', 'stage_id'], 'inst_stage_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instrument_stage_compatibilities');
        Schema::dropIfExists('instrument_jurisdiction_rules');
        Schema::dropIfExists('financing_channels');
        Schema::dropIfExists('financing_sources');
        Schema::dropIfExists('financing_type_instrument');
        Schema::dropIfExists('financial_instruments');
        Schema::dropIfExists('financing_types');
    }
};
