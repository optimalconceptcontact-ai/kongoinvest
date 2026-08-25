<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `team` was a free-text paragraph; it becomes a JSON list of
     * structured members (full name, role, LinkedIn, email, phone).
     * Existing free-text values can't be parsed into that shape, so
     * they're cleared rather than silently displayed as broken data.
     */
    public function up(): void
    {
        DB::table('projects')->update(['team' => null]);

        Schema::table('projects', function ($table) {
            $table->json('team')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function ($table) {
            $table->text('team')->nullable()->change();
        });
    }
};
