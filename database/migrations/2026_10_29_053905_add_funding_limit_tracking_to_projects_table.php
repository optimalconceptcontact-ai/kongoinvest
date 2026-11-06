<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ajoute deux colonnes pour tracker les notifications de limites de financement :
     * - funding_limit_notified_at : date d'envoi de la notification initiale
     * - funding_limit_deadline : date limite (14 jours après notification) pour corriger le montant
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Timestamp de la première notification envoyée à l'entrepreneur
            // concernant le dépassement des limites de financement
            $table->timestamp('funding_limit_notified_at')->nullable()->after('funding_currency');

            // Date limite (grace period de 14 jours) pour que l'entrepreneur
            // corrige le montant de financement de son projet
            $table->timestamp('funding_limit_deadline')->nullable()->after('funding_limit_notified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['funding_limit_notified_at', 'funding_limit_deadline']);
        });
    }
};
