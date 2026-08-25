<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_subscriptions', function (Blueprint $table) {
            // Which of the plan's two prices this specific subscription was
            // bought at — 'monthly' or 'annual'. The plan itself now offers
            // both, so the period is a property of the subscription
            // instance, not the plan definition.
            $table->string('billing_period')->default('monthly')->after('subscription_plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('plan_subscriptions', function (Blueprint $table) {
            $table->dropColumn('billing_period');
        });
    }
};
