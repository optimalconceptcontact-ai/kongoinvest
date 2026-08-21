<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A project normally sits in one province, but an Idea-stage project
     * (no fixed base yet) may span up to 3 — the single `province`
     * column can't express that, so it becomes a JSON list everywhere.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->json('provinces')->nullable()->after('country_code');
        });

        DB::table('projects')->whereNotNull('province')->orderBy('id')->each(function ($project) {
            DB::table('projects')->where('id', $project->id)->update([
                'provinces' => json_encode([$project->province]),
            ]);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['province']);
            $table->dropColumn('province');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('province')->nullable()->after('country_code');
        });

        DB::table('projects')->whereNotNull('provinces')->orderBy('id')->each(function ($project) {
            $provinces = json_decode($project->provinces, true) ?? [];
            DB::table('projects')->where('id', $project->id)->update([
                'province' => $provinces[0] ?? null,
            ]);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->index('province');
            $table->dropColumn('provinces');
        });
    }
};
