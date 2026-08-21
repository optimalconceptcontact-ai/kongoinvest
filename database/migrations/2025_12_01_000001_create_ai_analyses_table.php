<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();

            $table->string('status')->default('pending'); // App\Enums\AiRequestStatus
            $table->string('provider')->nullable();

            // The full structured ProjectAnalysisData, once completed —
            // spec §57: structured output via Spatie Data, never free
            // text the frontend has to parse itself.
            $table->json('content')->nullable();
            $table->unsignedTinyInteger('investment_score')->nullable(); // denormalized from content for fast queries/sorting

            $table->text('failure_reason')->nullable();
            $table->unsignedInteger('tokens_used')->nullable();

            $table->timestamps();

            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_analyses');
    }
};
