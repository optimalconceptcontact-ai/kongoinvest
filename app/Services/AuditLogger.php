<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

/**
 * The one place that writes to audit_logs. Spec §49 lists exactly which
 * actions must be audited — login, role changes, KYC/document access,
 * payment, Escrow release/refund, contract acceptance, admin actions —
 * and §90 requires actor + action + subject + metadata + ip + user agent
 * + timestamp on every row. Callers never build an AuditLog row by hand;
 * they call log(), which guarantees that shape every time.
 */
class AuditLogger
{
    public function log(?User $actor, string $action, ?Model $subject = null, array $metadata = []): AuditLog
    {
        return AuditLog::create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}
