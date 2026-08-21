<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * City selection is multiple too — one city normally, or several
     * spanning the chosen provinces when the project has more than one
     * (Idea stage). Same JSON-list treatment as `provinces`.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->json('cities')->nullable()->after('provinces');
        });

        DB::table('projects')->whereNotNull('city')->orderBy('id')->each(function ($project) {
            DB::table('projects')->where('id', $project->id)->update([
                'cities' => json_encode([$project->city]),
            ]);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('city');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('city')->nullable()->after('provinces');
        });

        DB::table('projects')->whereNotNull('cities')->orderBy('id')->each(function ($project) {
            $cities = json_decode($project->cities, true) ?? [];
            DB::table('projects')->where('id', $project->id)->update([
                'city' => $cities[0] ?? null,
            ]);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('cities');
        });
    }
};
