<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Spec §84 lists escrow_events as its own table, separate from
        // the generic audit_logs (Phase 9). The distinction is real: an
        // EscrowEvent is a first-class part of the Escrow's own state
        // machine (rendered as a timeline in the UI, queried by
        // "show me every escrow that transitioned to disputed this
        // week"), where audit_logs is a flat security/compliance trail
        // across every domain. Every EscrowEvent also gets an
        // audit_logs row (via AuditLogger) — this table is additive,
        // not a replacement for the audit trail.
        Schema::create('escrow_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escrow_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('escrow_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escrow_events');
    }
};
