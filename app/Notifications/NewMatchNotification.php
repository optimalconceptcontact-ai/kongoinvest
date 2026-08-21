<?php

namespace App\Notifications;

use App\Models\MatchModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewMatchNotification extends Notification
{
    use Queueable;

    public function __construct(public MatchModel $match) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'new_match',
            'match_id' => $this->match->id,
            'score' => $this->match->score,
            'project_title' => $this->match->project->title,
            'url' => "/projects/{$this->match->project->slug}",
        ];
    }
}
