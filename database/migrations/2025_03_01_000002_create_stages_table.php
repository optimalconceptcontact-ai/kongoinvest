<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Spec §85 lists "InvestmentStage" among the suggested PHP enums,
        // but §18 (investors select multiple stages), §84 (project_stage /
        // investor_stage pivot tables), and §131 (admin manages "stages"
        // content) all point to a real, admin-editable, FK-referenced
        // taxonomy — so this is a table, not an enum, same treatment as
        // Sector. The fixed vocabulary from §18 is what SectorsSeeder /
        // StagesSeeder below populate it with.
        Schema::create('stages', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // idea, pre_seed, seed, early_stage, growth, expansion, established
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stages');
    }
};
