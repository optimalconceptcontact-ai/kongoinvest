<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plan_subscriptions', function (Blueprint $table) {
            // Marks the one-time 7-day grace extension granted when a paid
            // subscription expires — tells the expiry command a row already
            // had its grace period (downgrade next) vs never (extend now).
            $table->timestamp('grace_extended_at')->nullable()->after('ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_subscriptions', function (Blueprint $table) {
            $table->dropColumn('grace_extended_at');
        });
    }
};
