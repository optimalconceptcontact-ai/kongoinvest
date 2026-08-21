<?php

namespace App\Events;

use App\Models\MatchModel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Spec §116. No listener is attached yet — NewMatchNotification belongs
 * to Phase 13 (Notifications), which will listen for this event rather
 * than this Job dispatching a notification directly. Firing the event
 * now means Phase 13 doesn't need to touch this scoring code at all.
 */
class MatchCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public MatchModel $match) {}
}
