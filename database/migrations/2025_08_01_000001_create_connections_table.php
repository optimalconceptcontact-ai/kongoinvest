<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('investor_profile_id')->constrained()->cascadeOnDelete();
            // Denormalized on purpose: the request always comes from the
            // investor today (spec §13), but recording who explicitly —
            // rather than inferring it from investor_profile_id — keeps
            // this row meaningful if a future phase allows entrepreneurs
            // to initiate too, without a migration.
            $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();

            $table->string('status')->default('pending'); // App\Enums\ConnectionStatus
            $table->text('message')->nullable(); // optional intro note from the investor
            $table->timestamp('responded_at')->nullable();

            $table->timestamps();

            // No hard unique constraint on (project_id, investor_profile_id):
            // a rejected or cancelled request can be followed by a new
            // one later. RequestConnection enforces "no existing pending
            // or accepted row for this pair" at the application level
            // instead, which a DB unique index couldn't express (it
            // would also block re-requesting after a rejection).
            $table->index(['project_id', 'investor_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connections');
    }
};
