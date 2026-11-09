<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financing_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('title')->nullable();
            $table->unsignedBigInteger('total_amount_minor');
            $table->string('currency', 3)->default('USD');
            $table->unsignedBigInteger('min_acceptable_minor')->nullable();
            $table->decimal('equity_dilution_percentage_target', 5, 2)->nullable();
            $table->foreignId('preferred_channel_id')->nullable()->constrained('financing_channels')->nullOnDelete();
            $table->string('status', 30)->default('published')->index(); // draft, published, in_review, funding_active, closed, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('financing_request_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financing_request_id')->constrained('financing_requests')->cascadeOnDelete();
            $table->foreignId('financial_instrument_id')->constrained('financial_instruments')->cascadeOnDelete();
            $table->unsignedBigInteger('target_amount_minor');
            $table->unsignedBigInteger('min_amount_minor')->nullable();
            $table->boolean('is_mandatory')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 30)->default('seeking')->index(); // seeking, partially_pledged, committed, funded
            $table->text('terms_summary')->nullable();
            $table->timestamps();
        });

        Schema::create('financing_terms', function (Blueprint $table) {
            $table->id();
            $table->morphs('termable'); // financing_request_components, financing_offers, etc.
            $table->unsignedBigInteger('valuation_pre_money_minor')->nullable();
            $table->unsignedBigInteger('valuation_post_money_minor')->nullable();
            $table->integer('interest_rate_bps')->nullable(); // 500 = 5.00%
            $table->unsignedInteger('duration_months')->nullable();
            $table->unsignedInteger('grace_period_months')->nullable();
            $table->decimal('revenue_share_percentage', 5, 2)->nullable();
            $table->unsignedBigInteger('cap_minor')->nullable();
            $table->integer('discount_rate_bps')->nullable();
            $table->boolean('collateral_required')->default(false);
            $table->text('collateral_description')->nullable();
            $table->json('covenants')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('financing_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investor_profile_id')->constrained('investor_profiles')->cascadeOnDelete();
            $table->foreignId('financing_request_id')->nullable()->constrained('financing_requests')->nullOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->unsignedBigInteger('total_offered_minor');
            $table->string('currency', 3)->default('USD');
            $table->string('offer_type', 30)->default('indicative')->index(); // indicative, firm, counter_offer
            $table->string('status', 30)->default('pending')->index(); // pending, accepted, rejected, negotiating, converted_to_deal
            $table->date('valid_until')->nullable();
            $table->timestamps();
        });

        Schema::create('financing_offer_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financing_offer_id')->constrained('financing_offers')->cascadeOnDelete();
            $table->foreignId('financial_instrument_id')->constrained('financial_instruments')->cascadeOnDelete();
            $table->unsignedBigInteger('offered_amount_minor');
            $table->timestamps();
        });

        Schema::create('investor_profile_instruments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investor_profile_id')->constrained('investor_profiles')->cascadeOnDelete();
            $table->foreignId('financial_instrument_id')->constrained('financial_instruments')->cascadeOnDelete();
            $table->unsignedBigInteger('ticket_min_minor')->nullable();
            $table->unsignedBigInteger('ticket_max_minor')->nullable();
            $table->timestamps();

            $table->unique(['investor_profile_id', 'financial_instrument_id'], 'inv_profile_inst_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_profile_instruments');
        Schema::dropIfExists('financing_offer_components');
        Schema::dropIfExists('financing_offers');
        Schema::dropIfExists('financing_terms');
        Schema::dropIfExists('financing_request_components');
        Schema::dropIfExists('financing_requests');
    }
};
