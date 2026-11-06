<?php

namespace App\Notifications;

use App\Models\Project;
use App\Services\FundingLimitService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notification envoyée aux entrepreneurs dont les projets dépassent les limites de financement.
 *
 * Cette notification informe l'entrepreneur qu'il dispose d'un grace period de 14 jours
 * pour corriger le montant de financement de son projet. Elle est envoyée via le canal
 * 'database' uniquement (notifications in-app).
 */
class ProjectFundingLimitNotification extends Notification
{
    use Queueable;

    /**
     * Crée une nouvelle instance de notification.
     *
     * @param  Project  $project  Le projet concerné par le dépassement
     * @param  Carbon  $deadline  Date limite pour corriger le montant (14 jours après notification)
     */
    public function __construct(public Project $project, public Carbon $deadline) {}

    /**
     * Définit les canaux de diffusion de la notification.
     *
     * @param  object  $notifiable  L'utilisateur notifiable (entrepreneur)
     * @return array<int, string> Tableau des canaux ('database' uniquement)
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Génère la représentation en base de données de la notification.
     *
     * @param  object  $notifiable  L'utilisateur notifiable
     * @return array<string, mixed> Données stockées dans la colonne 'data' de la table notifications
     */
    public function toDatabase(object $notifiable): array
    {
        $service = app(FundingLimitService::class);
        $limits = $service->getLimits($this->project->funding_currency);

        return [
            'type' => 'project_funding_limit',
            'project_id' => $this->project->id,
            'project_title' => $this->project->title,
            'currency' => $this->project->funding_currency,
            'current_amount' => $this->project->funding_target_minor,
            'current_amount_formatted' => $service->formatAmount($this->project->funding_target_minor, $this->project->funding_currency),
            'min_limit' => $limits['min'],
            'max_limit' => $limits['max'],
            'min_limit_formatted' => $service->formatAmount($limits['min'], $this->project->funding_currency),
            'max_limit_formatted' => $service->formatAmount($limits['max'], $this->project->funding_currency),
            'deadline' => $this->deadline->toIso8601String(),
            'deadline_formatted' => $this->deadline->translatedFormat('d F Y'),
            'url' => route('entrepreneur.projects.edit', ['project' => $this->project->slug]),
        ];
    }
}
