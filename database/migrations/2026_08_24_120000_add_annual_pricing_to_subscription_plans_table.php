<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            // Both nullable — Free has neither; a paid plan without a real
            // Stripe annual Price yet just can't be checked out annually
            // (BillingController::subscribe() guards on this).
            $table->integer('annual_price_minor')->nullable()->after('price_minor');
            $table->string('annual_stripe_price_id')->nullable()->after('stripe_price_id');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['annual_price_minor', 'annual_stripe_price_id']);
        });
    }
};
