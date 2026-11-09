<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * In-app notification sent to the entrepreneur when an administrator
 * approves or rejects a submitted project.
 */
class ProjectModerationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Project $project,
        public bool $approved,
        public ?string $notes = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => $this->approved ? 'project_approved' : 'project_rejected',
            'project_id' => $this->project->id,
            'project_title' => $this->project->title,
            'approved' => $this->approved,
            'notes' => $this->notes,
            'url' => route('entrepreneur.projects.index'),
        ];
    }
}
