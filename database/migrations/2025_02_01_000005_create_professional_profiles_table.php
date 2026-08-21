<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();

            $table->string('specialty')->nullable(); // e.g. legal, financial, technical advisor
            $table->text('bio')->nullable();
            $table->unsignedTinyInteger('years_experience')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('city')->nullable();
            $table->boolean('onboarding_completed')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_profiles');
    }
};
