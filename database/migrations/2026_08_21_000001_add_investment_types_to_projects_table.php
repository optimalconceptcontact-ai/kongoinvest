<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The entrepreneur picks exactly 3 App\Enums\FundingType values their
     * project is open to at creation time — the investor later narrows
     * this down to one when requesting AI contract generation.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->json('investment_types')->nullable()->after('funding_currency');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('investment_types');
        });
    }
};
