<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            // Nullable, not because a conversation can currently exist
            // without a deal room (it can't — every conversation today
            // is created by AcceptConnection), but so this table doesn't
            // need another migration if a future phase adds
            // deal-room-independent direct messaging.
            $table->foreignId('deal_room_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
