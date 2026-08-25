<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investor_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['investor_user_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_projects');
    }
};
