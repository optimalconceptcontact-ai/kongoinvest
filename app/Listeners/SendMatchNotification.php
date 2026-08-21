<?php

namespace App\Listeners;

use App\Events\MatchCreated;
use App\Notifications\NewMatchNotification;

class SendMatchNotification
{
    public function handle(MatchCreated $event): void
    {
        // Both sides of a match benefit from knowing — the entrepreneur
        // sees a new investor interested, the investor sees a new
        // recommended project.
        $event->match->project->user->notify(new NewMatchNotification($event->match));
        $event->match->investorProfile->user->notify(new NewMatchNotification($event->match));
    }
}
