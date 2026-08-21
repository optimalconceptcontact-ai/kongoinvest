<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('investor_profile_id')->constrained()->cascadeOnDelete();

            $table->unsignedTinyInteger('score'); // 0-100, final blended score
            // Per-criterion breakdown: [{key, label, score, weight,
            // weightedContribution, source}, ...] — this IS the
            // explainability the spec insists on (§23, §24, §52): never
            // show a bare number without the "why".
            $table->json('breakdown');

            $table->string('status')->default('pending'); // App\Enums\MatchStatus
            $table->timestamp('computed_at');

            $table->timestamps();

            // A pair is recomputed in place, never duplicated — the job
            // that (re)computes a match always updateOrCreate()s on this
            // pair (spec §41 idempotency, applied here even though this
            // isn't a payment).
            $table->unique(['project_id', 'investor_profile_id']);
            $table->index('score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
