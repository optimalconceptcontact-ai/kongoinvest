<?php

namespace App\Actions\Contracts;

use App\Enums\WorkspaceStatus;
use App\Models\InvestmentContractWorkspace;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

/**
 * Consentement à l'analyse IA des conversations (prompt §14) : chaque
 * partie consent séparément ; l'analyse ne peut JAMAIS s'exécuter sans
 * les deux consentements explicites et traçables (date + audit). Une
 * absence de consentement n'empêche pas la négociation humaine de
 * continuer — elle empêche seulement l'analyse IA.
 */
class RecordAnalysisConsent
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(User $user, InvestmentContractWorkspace $workspace): InvestmentContractWorkspace
    {
        if (! $workspace->isParticipant($user)) {
            throw ValidationException::withMessages([
                'consent' => 'Seuls les participants de la salle de négociation peuvent consentir à l\'analyse.',
            ]);
        }

        if ($workspace->isClosed() || $workspace->status === WorkspaceStatus::Cancelled) {
            throw ValidationException::withMessages([
                'workspace' => "L'opération est clôturée ou annulée : aucun consentement ne peut plus être enregistré.",
            ]);
        }

        $side = $workspace->isFounder($user) ? 'entrepreneur' : 'investor';
        $field = $side.'_analysis_consent_at';

        // Consentement idempotent : un double clic ne crée ni une
        // seconde date ni un second journal d'audit.
        if ($workspace->$field !== null) {
            return $workspace->fresh();
        }

        $workspace->forceFill([
            $field => now(),
            'last_activity_at' => now(),
        ])->save();

        $this->audit->log($user, 'workspace.ai_analysis_consent', $workspace, [
            'side' => $side,
        ]);

        return $workspace->fresh();
    }
}
