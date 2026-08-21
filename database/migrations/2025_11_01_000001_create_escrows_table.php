<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escrows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_room_id')->constrained()->cascadeOnDelete();
            // One Escrow per accepted Contract — the contract IS the
            // agreement being funded (spec §33's flow: Payment -> Escrow
            // -> Deal Room -> Agreement -> confirmation -> Release/Refund).
            $table->foreignId('contract_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('investor_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('entrepreneur_user_id')->constrained('users')->cascadeOnDelete();

            // Set once funding succeeds — the Payment that actually moved
            // the money (spec §97: Provider -> Payment transaction ->
            // Escrow -> Release/Refund; the provider never determines
            // Escrow's business logic directly, it only funds it).
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3);

            $table->string('status')->default('pending'); // App\Enums\EscrowStatus

            $table->string('payout_reference')->nullable(); // set once release is confirmed
            $table->string('refund_reference')->nullable(); // the provider's refund ID, once refunded

            $table->timestamp('funded_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escrows');
    }
};
