<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payer_id')->constrained('users')->cascadeOnDelete();

            $table->string('purpose'); // App\Enums\PaymentPurpose
            // What this payment is actually for — a Subscription today,
            // an Investment once Phase 11's Escrow exists. Polymorphic
            // rather than a nullable subscription_id column so Escrow
            // doesn't need its own separate payments table (spec §97:
            // Provider -> Payment transaction -> Escrow).
            $table->nullableMorphs('payable');

            $table->string('provider'); // App\Enums\PaymentProviderName
            $table->string('provider_reference')->nullable(); // the gateway's transaction/intent ID, once known

            // Minor units + explicit currency, never float (spec §146).
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3);

            $table->string('status')->default('pending'); // App\Enums\PaymentStatus
            $table->string('failure_reason')->nullable();

            // Spec §41: every payment attempt carries an idempotency key
            // so a retried request (double-click, network retry) can
            // never create two payment rows for the same intent. Set
            // once at creation, never changed.
            $table->string('idempotency_key')->unique();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['payer_id', 'status']);
            $table->index('provider_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
