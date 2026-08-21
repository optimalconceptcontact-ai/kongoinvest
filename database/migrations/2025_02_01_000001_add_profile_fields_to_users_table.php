<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Fields shared by every role. Role-specific data (investor
            // tickets, entrepreneur company, etc.) lives in the profile
            // tables, not here — keeps `users` purely about identity/auth.
            $table->string('phone')->nullable()->after('email');
            $table->string('country_code', 2)->nullable()->after('phone');
            $table->string('city')->nullable()->after('country_code');
            $table->string('locale', 5)->default('fr')->after('city');
            $table->string('timezone')->default('Africa/Kinshasa')->after('locale');

            // Contact-visibility toggles (spec §79, §108-109). Enforced
            // server-side wherever contact info is serialized — never a
            // frontend-only concern.
            $table->boolean('show_email')->default(false)->after('timezone');
            $table->boolean('show_phone')->default(false)->after('show_email');
            $table->boolean('show_website')->default(false)->after('show_phone');
            $table->boolean('show_socials')->default(false)->after('show_website');
            $table->boolean('show_address')->default(false)->after('show_socials');

            $table->string('website')->nullable()->after('show_address');
            $table->json('social_links')->nullable()->after('website');

            $table->timestamp('last_active_at')->nullable()->after('social_links');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'phone', 'country_code', 'city', 'locale', 'timezone',
                'show_email', 'show_phone', 'show_website', 'show_socials', 'show_address',
                'website', 'social_links', 'last_active_at',
            ]);
        });
    }
};
