<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guide_articles', function (Blueprint $table) {
            $table->id();
            $table->string('section', 40); // Tab key, see GuideArticle::SECTIONS
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('content'); // Rich text (HTML) authored in the admin
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['section', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guide_articles');
    }
};
