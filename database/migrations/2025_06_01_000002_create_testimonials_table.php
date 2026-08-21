<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role')->nullable();
            $table->string('company')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->text('content');
            $table->string('photo_path')->nullable();
            // Deliberately defaults to false — spec §69: "Ne jamais
            // inventer de témoignages." Nothing in this table is ever
            // seeded; only a real user's real words, entered by an admin
            // (Phase 7 CRUD) and explicitly published, ever appear here.
            $table->boolean('is_published')->default(false);
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
