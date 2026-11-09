<?php

namespace App\Actions\Contracts;

use App\Enums\WorkspaceStatus;
use App\Models\InvestmentContractWorkspace;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Compliance\InvestmentComplianceGateService;
use App\Services\Contracts\FinancialConsistencyService;
use App\Services\Contracts\LegalComplianceService;
use Illuminate\Validation\ValidationException;

/**
 * Clôture de l'opération (prompt §17). Quatre portes de sécurité —
 * toutes déterministes, toutes vérifiées ici au moment de la
 * clôture et jamais « assumées » :
 * 1. Aucun contrôle financier critique non résolu ;
 * 2. Aucun contrôle juridique critique non résolu ;
 * 3. Toutes les conditions conduites à terme, non satisfaites ou
 *    explicitement levées — rien en attente ;
 * 4. La revue juridique de l'opération est horodatée.
 */
class CloseOperation
{
    public function __construct(
        private AuditLogger $audit,
        private FinancialConsistencyService $consistency,
        private LegalComplianceService $compliance,
        private InvestmentComplianceGateService $investmentCompliance,
    ) {}

    public function handle(User $user, InvestmentContractWorkspace $workspace): InvestmentContractWorkspace
    {
        if ($workspace->isClosed() || $workspace->status === WorkspaceStatus::Cancelled) {
            throw ValidationException::withMessages([
                'workspace' => 'L\'opération est déjà clôturée ou annulée.',
            ]);
        }

        if (! $workspace->isParticipant($user)) {
            throw ValidationException::withMessages([
                'workspace' => 'Seuls les participants de l\'opération la clôturent.',
            ]);
        }

        // Compliance freeze: a blocked/suspended case, an open sanctions
        // alert, a rejected KYC or an unapproved identity/KYB/UBO
        // checkpoint on either side prevents closing the operation
        // until compliance resolves it.
        $this->investmentCompliance->assertAllows(
            $this->investmentCompliance->evaluateWorkspace(
                $workspace,
                InvestmentComplianceGateService::STAGE_CLOSING,
            ),
            'workspace',
        );

        if ($this->consistency->hasOpenBlockingChecks($workspace)) {
            throw ValidationException::withMessages([
                'workspace' => 'Des contrôles financiers critiques non résolus bloquent la finalisation — relancez les contrôles et traitez les alertes.',
            ]);
        }

        if ($this->compliance->hasOpenBlockingChecks($workspace)) {
            throw ValidationException::withMessages([
                'workspace' => 'Des contrôles de conformité juridique critiques non résolus bloquent la finalisation — relancez les contrôles et traitez les alertes.',
            ]);
        }

        $openConditions = $workspace->conditions()
            ->where('status', 'pending')
            ->exists();

        if ($openConditions) {
            throw ValidationException::withMessages([
                'workspace' => 'Des conditions restent en attente : conduisez-les à terme, marquez-les impossible ou renoncez explicitement.',
            ]);
        }

        if ($workspace->legal_reviewed_at === null) {
            throw ValidationException::withMessages([
                'workspace' => 'La revue juridique de l\'opération n\'a pas encore été conduite.',
            ]);
        }

        $workspace->forceFill([
            'status' => WorkspaceStatus::Closed->value,
            'closed_at' => now(),
        ])->save();

        $this->audit->log($user, 'workspace.closed', $workspace, [
            'workspace_id' => $workspace->id,
        ]);

        return $workspace->fresh();
    }
}
