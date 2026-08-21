<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // App\Enums\AiInteractionType — entrepreneur_coach or investor_advisor
            $table->timestamps();

            $table->index(['user_id', 'project_id', 'type']);
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role'); // 'user' | 'assistant'
            $table->text('content');
            // Structured payload for assistant turns (spec §57) — the
            // frontend renders this, not the raw `content` text, when
            // present. `content` still holds a human-readable summary so
            // conversation history can display something even before
            // parsing.
            $table->json('structured_content')->nullable();
            $table->unsignedInteger('tokens_used')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
    }
};
