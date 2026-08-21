<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // App\Enums\SubscriptionPlanKey
            $table->string('name');

            // Which role this plan is for — null means any role may pick
            // it (only Free is scoped this way). Enforced in
            // SubscriptionService::switchPlan(), not just in the UI
            // (spec §100): an entrepreneur can never end up on the
            // Investor plan, even via a direct request.
            $table->string('role_scope')->nullable(); // App\Enums\UserRole

            $table->unsignedInteger('price_minor')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('billing_period')->default('monthly'); // monthly, yearly

            // Numeric caps, e.g. {"max_active_projects": 1, "ai_monthly_token_quota": 20000}
            $table->json('limits')->nullable();
            // Boolean capability flags — values from App\Enums\SubscriptionFeature
            $table->json('features')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
