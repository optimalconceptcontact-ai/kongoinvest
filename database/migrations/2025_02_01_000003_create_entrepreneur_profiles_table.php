<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entrepreneur_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();

            $table->string('job_title')->nullable();
            $table->text('bio')->nullable();
            $table->text('experience')->nullable();

            // Spec §15: an entrepreneur's project is always implanted in
            // the DRC. Stored explicitly (not inferred from user.country)
            // so it survives even if we later allow diaspora entrepreneurs
            // whose personal residence differs from the project's country.
            $table->string('country_code', 2)->default('CD');
            $table->string('province')->nullable(); // App\Enums\DrcProvince
            $table->string('city')->nullable();

            $table->unsignedTinyInteger('years_experience')->nullable();
            $table->boolean('onboarding_completed')->default(false);

            $table->timestamps();

            $table->index('province');
        });

        // Belt-and-suspenders on top of the FormRequest validation: even
        // a direct DB write can't set an entrepreneur's country to
        // anything but the DRC (spec §15).
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE entrepreneur_profiles ADD CONSTRAINT chk_entrepreneur_country_is_drc CHECK (country_code = 'CD')");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('entrepreneur_profiles');
    }
};
