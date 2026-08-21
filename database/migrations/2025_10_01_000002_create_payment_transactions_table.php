<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();

            $table->string('type'); // e.g. payment_intent.succeeded, CHECKOUT.ORDER.APPROVED, webhook.orange_money.notify
            // The provider's own event ID. This is the actual
            // double-processing guard from spec §40-41: a webhook
            // delivered twice (every provider's docs warn this happens)
            // hits this unique constraint on the second attempt and is
            // a no-op, never a duplicated balance change.
            $table->string('provider_event_id')->nullable();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->unique(['payment_id', 'provider_event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
