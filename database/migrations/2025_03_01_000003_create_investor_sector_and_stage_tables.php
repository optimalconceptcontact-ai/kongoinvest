<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_sector', function (Blueprint $table) {
            $table->foreignId('investor_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sector_id')->constrained()->cascadeOnDelete();
            $table->primary(['investor_profile_id', 'sector_id']);
        });

        Schema::create('investor_stage', function (Blueprint $table) {
            $table->foreignId('investor_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained()->cascadeOnDelete();
            $table->primary(['investor_profile_id', 'stage_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_stage');
        Schema::dropIfExists('investor_sector');
    }
};
