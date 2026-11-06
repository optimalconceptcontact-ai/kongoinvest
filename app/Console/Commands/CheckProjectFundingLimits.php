<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Notifications\ProjectFundingLimitNotification;
use App\Services\FundingLimitService;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('projects:check-funding-limits {--dry-run : Simulate execution without sending notifications}')]
#[Description('Vérifie les projets dont le montant de financement dépasse les limites et notifie les entrepreneurs')]
class CheckProjectFundingLimits extends Command
{
    /**
     * Nombre de jours du grace period pour corriger les limites.
     */
    private const GRACE_PERIOD_DAYS = 14;

    /**
     * Exécute la commande de vérification des limites de financement.
     *
     * Cette commande parcourt tous les projets non archivés et vérifie si leur
     * funding_target_minor respecte les limites définies par devise. Pour les projets
     * hors limites qui n'ont jamais été notifiés, elle envoie une notification et
     * définit une deadline de 14 jours. Pour les projets déjà notifiés, elle affiche
     * l'état de la correction.
     *
     * @return int Code de sortie (0 = succès)
     */
    public function handle(FundingLimitService $fundingLimitService): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('🔍 Mode simulation activé - aucune notification ne sera envoyée.');
            $this->newLine();
        }

        // Récupère tous les projets non archivés avec un montant de financement défini
        $projects = Project::whereNull('archived_at')
            ->whereNotNull('funding_target_minor')
            ->with('user')
            ->get();

        $totalChecked = 0;
        $totalNotified = 0;
        $totalPending = 0;
        $totalDeadlinePassed = 0;

        $this->info("📊 Vérification de {$projects->count()} projet(s)...");
        $this->newLine();

        foreach ($projects as $project) {
            $totalChecked++;

            // Vérifie si le projet est hors limites
            if (! $project->isOutsideFundingLimits()) {
                // Projet dans les limites — réinitialiser les champs de tracking si présents
                if ($project->funding_limit_notified_at !== null && ! $isDryRun) {
                    $project->update([
                        'funding_limit_notified_at' => null,
                        'funding_limit_deadline' => null,
                    ]);
                    $this->line("✅ {$project->title} : montant corrigé, tracking réinitialisé.");
                }

                continue;
            }

            // Le projet est hors limites
            $limits = $fundingLimitService->getLimits($project->funding_currency);
            $currentFormatted = $fundingLimitService->formatAmount($project->funding_target_minor, $project->funding_currency);

            // Cas 1 : Jamais notifié — envoyer la notification initiale
            if ($project->funding_limit_notified_at === null) {
                $deadline = Carbon::now()->addDays(self::GRACE_PERIOD_DAYS);

                if (! $isDryRun) {
                    // Envoie la notification à l'entrepreneur
                    $project->user->notify(new ProjectFundingLimitNotification($project, $deadline));

                    // Enregistre la date de notification et la deadline
                    $project->update([
                        'funding_limit_notified_at' => Carbon::now(),
                        'funding_limit_deadline' => $deadline,
                    ]);
                }

                $totalNotified++;
                $this->warn("📧 {$project->title} : notification envoyée (montant actuel : {$currentFormatted}, deadline : {$deadline->format('d/m/Y')})");

                continue;
            }

            // Cas 2 : Déjà notifié — vérifier l'état de la deadline
            $totalPending++;

            if ($project->hasFundingLimitDeadlinePassed()) {
                $totalDeadlinePassed++;
                $this->error("⏰ {$project->title} : deadline dépassée ({$project->funding_limit_deadline->format('d/m/Y')}), montant toujours hors limites ({$currentFormatted})");
            } else {
                $daysRemaining = Carbon::now()->diffInDays($project->funding_limit_deadline, false);
                $this->line("⏳ {$project->title} : en attente de correction, {$daysRemaining} jour(s) restant(s) (montant : {$currentFormatted})");
            }
        }

        // Affichage du résumé
        $this->newLine();
        $this->info('📋 Résumé :');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Projets vérifiés', $totalChecked],
                ['Notifications envoyées', $totalNotified],
                ['En attente de correction', $totalPending],
                ['Deadlines dépassées', $totalDeadlinePassed],
            ]
        );

        if ($isDryRun) {
            $this->newLine();
            $this->comment('💡 Mode simulation - pour exécuter réellement, relancez sans --dry-run');
        }

        return self::SUCCESS;
    }
}
