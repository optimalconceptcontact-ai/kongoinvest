<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained()->restrictOnDelete();

            $table->string('status'); // App\Enums\SubscriptionStatus

            $table->timestamp('starts_at');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable(); // null = ongoing
            $table->timestamp('canceled_at')->nullable();

            // Populated once real billing lands in Phase 10 — a Free
            // plan subscription has no payment provider at all, which is
            // exactly why this is nullable rather than required.
            $table->string('payment_provider')->nullable();
            $table->string('provider_subscription_id')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
