<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Paid-subscription lifecycle: 5-day warning, 7-day grace on expiry,
// then back to Free — needs `php artisan schedule:run` every minute
// (cron) or the deadlines silently slip.
Schedule::command('subscriptions:process-expirations')->daily();

// Vérification quotidienne des limites de financement des projets.
// Envoie des notifications in-app aux entrepreneurs dont les projets
// dépassent les limites (min/max par devise) et gère le grace period
// de 14 jours pour corriger les montants.
Schedule::command('projects:check-funding-limits')
    ->dailyAt('08:00')
    ->description('Vérifie les limites de financement des projets');
