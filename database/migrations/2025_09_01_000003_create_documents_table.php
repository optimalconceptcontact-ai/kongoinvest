<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete(); // who uploaded it

            $table->string('title');
            $table->string('visibility')->default('private'); // App\Enums\DocumentVisibility

            // Set only while visibility = requested — who asked the
            // owner to share it (spec §46).
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index(['deal_room_id', 'visibility']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
