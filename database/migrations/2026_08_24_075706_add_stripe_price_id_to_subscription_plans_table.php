<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            // Null for Free and Coaching (never billed via Stripe) and for
            // Entrepreneur/Investor until a real Stripe Price is created —
            // BillingController::subscribe() refuses to charge without one.
            $table->string('stripe_price_id')->nullable()->after('price_minor');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('stripe_price_id');
        });
    }
};
