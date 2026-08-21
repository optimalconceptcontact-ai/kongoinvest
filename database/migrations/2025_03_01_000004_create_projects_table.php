<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // the entrepreneur

            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('executive_summary')->nullable();
            $table->text('problem')->nullable();
            $table->text('solution')->nullable();
            $table->text('business_model')->nullable();
            $table->text('market')->nullable();
            $table->text('competitive_advantage')->nullable();
            $table->text('team')->nullable();
            $table->text('traction')->nullable();
            $table->text('financial_information')->nullable();
            $table->text('impact')->nullable();
            $table->text('risks')->nullable();

            // Money — minor units + explicit currency, never float (§146).
            $table->unsignedBigInteger('funding_target_minor')->nullable();
            $table->unsignedBigInteger('funding_min_minor')->nullable();
            $table->unsignedBigInteger('funding_max_minor')->nullable();
            $table->string('funding_currency', 3)->default('USD');
            $table->unsignedBigInteger('funding_raised_minor')->default(0);

            // Spec §15/§19: a project's implantation is always the DRC.
            $table->string('country_code', 2)->default('CD');
            $table->string('province')->nullable(); // App\Enums\DrcProvince
            $table->string('city')->nullable();

            $table->string('website')->nullable();
            $table->json('social_links')->nullable();

            $table->string('status')->default('draft'); // App\Enums\ProjectStatus
            $table->string('visibility')->default('private'); // App\Enums\ProjectVisibility

            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'visibility']);
            $table->index('province');
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE projects ADD CONSTRAINT chk_project_country_is_drc CHECK (country_code = 'CD')");
        }

        Schema::create('project_sector', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sector_id')->constrained()->cascadeOnDelete();
            $table->primary(['project_id', 'sector_id']);
        });

        Schema::create('project_stage', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained()->cascadeOnDelete();
            $table->primary(['project_id', 'stage_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_stage');
        Schema::dropIfExists('project_sector');
        Schema::dropIfExists('projects');
    }
};
