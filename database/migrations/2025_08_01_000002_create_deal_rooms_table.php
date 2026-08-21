<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deal_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_id')->unique()->constrained()->cascadeOnDelete();
            // Denormalized from connection->project / connection->investorProfile
            // purely so Policies and future Phase 9-11 features (Documents,
            // KYC, Contracts, Escrow) can filter/join on the deal room
            // directly without traversing connection -> project every
            // time. The connection remains the source of truth.
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entrepreneur_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('investor_user_id')->constrained('users')->cascadeOnDelete();

            $table->string('status')->default('active'); // active, closed (closing is out of scope this phase)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_rooms');
    }
};
