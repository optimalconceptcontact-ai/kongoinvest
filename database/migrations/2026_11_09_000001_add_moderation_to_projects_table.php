<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->after('published_at');
            $table->timestamp('moderated_at')->nullable()->after('submitted_at');
            $table->text('moderation_notes')->nullable()->after('moderated_at');
            $table->foreignId('moderated_by_id')
                ->nullable()
                ->after('moderation_notes')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['submitted_at']);
            $table->dropConstrainedForeignId('moderated_by_id');
            $table->dropColumn(['submitted_at', 'moderated_at', 'moderation_notes']);
        });
    }
};
