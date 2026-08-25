<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Frees up the 'subscriptions' table name for Laravel Cashier's own
     * migrations (published alongside this one) — the app's own plan-tier
     * table moves to 'plan_subscriptions' instead of being replaced.
     */
    public function up(): void
    {
        Schema::rename('subscriptions', 'plan_subscriptions');
    }

    public function down(): void
    {
        Schema::rename('plan_subscriptions', 'subscriptions');
    }
};
