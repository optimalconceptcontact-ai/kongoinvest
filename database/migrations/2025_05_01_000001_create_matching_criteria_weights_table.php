<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matching_criteria_weights', function (Blueprint $table) {
            $table->id();
            $table->string('criterion')->unique(); // App\Enums\MatchCriterion
            $table->decimal('weight', 4, 3); // 0.000 - 1.000, all rows should sum to 1.0
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matching_criteria_weights');
    }
};
