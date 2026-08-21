<?php

namespace App\Listeners;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * Spec §49 explicitly lists login/logout in the audit trail — this was
 * a real gap until now. Fortify handles the actual authentication
 * (spec §5); this listener only observes the standard Laravel auth
 * events it fires, same "never reimplement what Fortify already does"
 * principle from Phase 1.
 */
class LogAuthenticationEvents
{
    public function __construct(private AuditLogger $audit) {}

    public function handleLogin(Login $event): void
    {
        $this->audit->log($event->user, 'auth.login');
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user) {
            $this->audit->log($event->user, 'auth.logout');
        }
    }
}
