<?php

namespace App\Services\Compliance;

use App\Enums\AmlAlertStatus;
use App\Enums\AmlAlertType;
use App\Enums\AmlRiskLevel;
use App\Enums\CheckSeverity;
use App\Enums\ComplianceCaseStatus;
use App\Enums\KycStatus;
use App\Models\AmlAlert;
use App\Models\BusinessVerification;
use App\Models\Company;
use App\Models\ComplianceCase;
use App\Models\Contract;
use App\Models\DealRoom;
use App\Models\Escrow;
use App\Models\InvestmentContractWorkspace;
use App\Models\Project;
use App\Models\User;
use App\Services\Compliance\Data\ComplianceGateIssue;
use App\Services\Compliance\Data\ComplianceGateResult;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Porte de conformité des opérations contractuelles et
 * d'investissement.
 *
 * Deux niveaux, jamais confondus :
 * 1. Les blocages durs, toujours actifs : dossier bloqué ou suspendu,
 *    alerte sanctions/critique ouverte, KYC rejeté ou expiré. Ils
 *    gèlent la signature, le séquestre et la clôture, quel que soit
 *    l'environnement.
 * 2. Les checkpoints progressifs, activables par configuration : une
 *    identité vérifiée, un KYB validé et des bénéficiaires effectifs
 *    vérifiés peuvent être exigés à des étapes précises (signature,
 *    financement, libération, clôture). Un dossier de conformité
 *    commencé mais non approuvé bloque déjà les étapes sensibles ;
 *    les opérations historiques sans aucune donnée de conformité
 *    restent lisibles et menables, sans jamais auto-approuver quoi
 *    que ce soit.
 */
class InvestmentComplianceGateService
{
    public const STAGE_SUBSCRIPTION = 'subscription';

    public const STAGE_DATA_ROOM = 'data_room';

    public const STAGE_WORKSPACE_OPENING = 'workspace_opening';

    public const STAGE_CONTRACT_ACCEPTANCE = 'contract_acceptance';

    public const STAGE_SIGNATURE = 'signature';

    public const STAGE_ESCROW_CREATION = 'escrow_creation';

    public const STAGE_ESCROW_FUNDING = 'escrow_funding';

    public const STAGE_ESCROW_RELEASE = 'escrow_release';

    public const STAGE_CLOSING = 'closing';

    /**
     * Évalue un utilisateur pour une étape donnée.
     */
    public function evaluateUser(
        User $user,
        string $stage,
        ?int $amountMinor = null,
        string $currency = 'USD',
    ): ComplianceGateResult {
        $issues = [];

        if ($this->hasBlockedCase($user)) {
            $issues[] = new ComplianceGateIssue(
                'compliance_case_blocked',
                "Le dossier de conformité de {$user->name} est bloqué ou suspendu : l'opération est gelée jusqu'à décision du service conformité.",
                CheckSeverity::Critical,
                $user->name,
            );
        }

        if ($this->hasOpenBlockingAlertForUser($user)) {
            $issues[] = new ComplianceGateIssue(
                'open_sanctions_alert',
                "Une alerte sanctions ou critique reste ouverte pour {$user->name} : aucune signature ni aucun mouvement de fonds avant traitement humain.",
                CheckSeverity::Critical,
                $user->name,
            );
        }

        $issues = array_merge($issues, $this->identityIssues($user, $stage));

        if ($amountMinor !== null && $amountMinor > 0) {
            $issues = array_merge($issues, $this->sourceOfFundsIssues($user, $amountMinor, $stage));
        }

        return ComplianceGateResult::of($issues);
    }

    /**
     * Évalue la société cible d'une opération (KYB, UBO, alertes).
     */
    public function evaluateCompany(?Company $company, string $stage): ComplianceGateResult
    {
        if ($company === null) {
            return ComplianceGateResult::allow();
        }

        $issues = [];
        $cases = $this->companyComplianceCases($company);

        if ($cases->contains(fn (ComplianceCase $case): bool => $case->isBlockedOrSuspended())) {
            $issues[] = new ComplianceGateIssue(
                'company_compliance_case_blocked',
                "Le dossier de conformité de la société cible {$company->name} est bloqué ou suspendu.",
                CheckSeverity::Critical,
                $company->name,
            );
        }

        if ($cases->contains(fn (ComplianceCase $case): bool => $this->caseHasBlockingAlert($case))) {
            $issues[] = new ComplianceGateIssue(
                'company_open_sanctions_alert',
                "Une alerte sanctions ou critique reste ouverte sur la société cible {$company->name}.",
                CheckSeverity::Critical,
                $company->name,
            );
        }

        $verification = $company->latestVerification()->first();
        $kybRequired = $this->stageRequires('kyb_required_stages', $stage);

        if ($verification === null) {
            if ($kybRequired) {
                $issues[] = new ComplianceGateIssue(
                    'kyb_missing',
                    "Aucun dossier KYB n'existe pour la société cible {$company->name} : la vérification est obligatoire à cette étape.",
                    CheckSeverity::Critical,
                    $company->name,
                );
            }
        } elseif ($verification->status === 'rejected') {
            $issues[] = new ComplianceGateIssue(
                'kyb_rejected',
                "Le dossier KYB de {$company->name} a été rejeté : l'opération ne peut pas avancer.",
                CheckSeverity::Critical,
                $company->name,
            );
        } elseif (! $verification->isVerified()) {
            $isCheckpoint = $this->stageRequires('kyb_checkpoint_stages', $stage);

            if ($kybRequired || $isCheckpoint) {
                $issues[] = new ComplianceGateIssue(
                    'kyb_not_verified',
                    "Le dossier KYB de {$company->name} n'est pas formellement validé (statut : {$verification->status}).",
                    CheckSeverity::Critical,
                    $company->name,
                );
            } else {
                $issues[] = new ComplianceGateIssue(
                    'kyb_not_verified',
                    "Le dossier KYB de {$company->name} reste à valider avant les étapes de signature et de clôture.",
                    CheckSeverity::High,
                    $company->name,
                );
            }
        }

        if ($verification !== null) {
            $issues = array_merge($issues, $this->beneficialOwnerIssues($verification, $stage));
        }

        return ComplianceGateResult::of($issues);
    }

    /**
     * Évalue les deux parties d'une opération, puis la société cible.
     */
    public function evaluateOperation(
        ?User $investor,
        ?User $founder,
        ?Company $company,
        string $stage,
        ?int $amountMinor = null,
        string $currency = 'USD',
    ): ComplianceGateResult {
        $result = ComplianceGateResult::allow();

        $parties = collect([$investor, $founder])
            ->filter()
            ->unique('id');

        foreach ($parties as $party) {
            $partyAmount = $investor !== null && $party->id === $investor->id
                ? $amountMinor
                : null;

            $result = $result->merge(
                $this->evaluateUser($party, $stage, $partyAmount, $currency),
            );
        }

        return $result->merge($this->evaluateCompany($company, $stage));
    }

    /**
     * Évalue une salle de négociation avant l'ouverture de son espace
     * contractuel.
     */
    public function evaluateDealRoom(DealRoom $dealRoom, string $stage = self::STAGE_WORKSPACE_OPENING, ?Company $company = null): ComplianceGateResult
    {
        return $this->evaluateOperation(
            $dealRoom->investor,
            $dealRoom->entrepreneur,
            $company,
            $stage,
        );
    }

    /**
     * Évalue un contrat historique (Acceptation, séquestre).
     */
    public function evaluateContract(Contract $contract, string $stage): ComplianceGateResult
    {
        $dealRoom = $contract->dealRoom;

        return $this->evaluateOperation(
            $dealRoom?->investor,
            $dealRoom?->entrepreneur,
            null,
            $stage,
            $contract->amount_minor,
            $contract->currency ?: 'USD',
        );
    }

    /**
     * Évalue un séquestre (création, financement, libération).
     */
    public function evaluateEscrow(Escrow $escrow, string $stage): ComplianceGateResult
    {
        return $this->evaluateOperation(
            $escrow->investor,
            $escrow->entrepreneur,
            null,
            $stage,
            $escrow->amount_minor,
            $escrow->currency ?: 'USD',
        );
    }

    /**
     * Évalue une opération de l'espace contractuel (signature, clôture).
     */
    public function evaluateWorkspace(InvestmentContractWorkspace $workspace, string $stage): ComplianceGateResult
    {
        return $this->evaluateOperation(
            $workspace->investor(),
            $workspace->founder(),
            $workspace->company,
            $stage,
            $workspace->total_investment_minor,
            $workspace->currency ?: 'USD',
        );
    }

    /**
     * Lève une ValidationException si une issue critique bloque.
     */
    public function assertAllows(ComplianceGateResult $result, string $field = 'compliance'): void
    {
        if ($result->allowed()) {
            return;
        }

        throw ValidationException::withMessages([
            $field => $result->blockingMessages(),
        ]);
    }

    /**
     * Compatibilité ascendante — un abonnement se finalise seulement
     * avec un KYC approuvé et sans blocage dur.
     */
    public function canFinalizeSubscription(User $investor): bool
    {
        if (! $this->evaluateUser($investor, self::STAGE_SUBSCRIPTION)->allowed()) {
            return false;
        }

        return (bool) $investor->kycProfile()->first()?->isVerifiedAndActive();
    }

    /**
     * Compatibilité ascendante — l'ouverture d'une Data Room exige
     * l'identité du porteur vérifiée et l'absence de blocage.
     */
    public function canOpenDataRoom(Project $project): bool
    {
        $founder = $project->user;

        if ($founder === null) {
            return false;
        }

        if (! (bool) $founder->kycProfile()->first()?->isVerifiedAndActive()) {
            return false;
        }

        if (! $this->evaluateUser($founder, self::STAGE_DATA_ROOM)->allowed()) {
            return false;
        }

        $case = $project->complianceCase;

        if ($case !== null && ($case->isBlockedOrSuspended() || $this->caseHasBlockingAlert($case))) {
            return false;
        }

        return true;
    }

    /**
     * Compatibilité ascendante — synthèse de clôture.
     *
     * @return array{can_close: bool, blocking_reasons: list<string>}
     */
    public function canTriggerClosing(User $investor, ?Company $targetCompany = null): array
    {
        $result = $this->evaluateOperation(
            $investor,
            null,
            $targetCompany,
            self::STAGE_CLOSING,
        );

        return [
            'can_close' => $result->allowed(),
            'blocking_reasons' => $result->blockingMessages(),
        ];
    }

    /**
     * @return list<ComplianceGateIssue>
     */
    private function identityIssues(User $user, string $stage): array
    {
        $profile = $user->kycProfile()->first();

        if ($profile !== null && in_array($profile->status, [KycStatus::Rejected, KycStatus::Expired], true)) {
            return [new ComplianceGateIssue(
                'kyc_rejected_or_expired',
                "Le KYC de {$user->name} est {$profile->status->label()} : l'opération ne peut pas avancer.",
                CheckSeverity::Critical,
                $user->name,
            )];
        }

        $identityRequired = $this->stageRequires('identity_required_stages', $stage);
        $isCheckpoint = $this->stageRequires('identity_checkpoint_stages', $stage);
        $complianceStarted = $this->hasComplianceFootprint($user);

        if ($identityRequired || ($isCheckpoint && $complianceStarted)) {
            if (! ($profile?->isVerifiedAndActive() ?? false)) {
                return [new ComplianceGateIssue(
                    'identity_not_verified',
                    "L'identité de {$user->name} doit être vérifiée et active avant l'étape « {$stage} ».",
                    CheckSeverity::Critical,
                    $user->name,
                )];
            }
        }

        return [];
    }

    /**
     * @return list<ComplianceGateIssue>
     */
    private function sourceOfFundsIssues(User $user, int $amountMinor, string $stage): array
    {
        $threshold = (int) config('compliance.gates.source_of_funds_threshold_minor', 1_000_000);
        $requiredForStage = $this->stageRequires('source_of_funds_required_stages', $stage);

        if (! $requiredForStage && (! $this->hasComplianceFootprint($user) || $amountMinor < $threshold)) {
            return [];
        }

        $declaration = $user->sourceOfFundsDeclarations()->latest('id')->first();

        if ($declaration === null) {
            return [new ComplianceGateIssue(
                'source_of_funds_missing',
                "Une déclaration d'origine des fonds est requise pour {$user->name} au-delà du seuil configuré.",
                CheckSeverity::Critical,
                $user->name,
            )];
        }

        if (! $declaration->is_verified) {
            return [new ComplianceGateIssue(
                'source_of_funds_unverified',
                "La déclaration d'origine des fonds de {$user->name} est enregistrée mais pas encore vérifiée par la conformité.",
                CheckSeverity::High,
                $user->name,
            )];
        }

        return [];
    }

    /**
     * @return list<ComplianceGateIssue>
     */
    private function beneficialOwnerIssues(BusinessVerification $verification, string $stage): array
    {
        $issues = [];
        $case = $verification->complianceCase;

        $hasOpenUnidentifiedUbo = $case !== null
            && $case->alerts()
                ->where('alert_type', AmlAlertType::UnidentifiedUbo->value)
                ->whereIn('status', $this->openAlertStatuses())
                ->exists();

        if ($hasOpenUnidentifiedUbo) {
            $issues[] = new ComplianceGateIssue(
                'unidentified_ubo',
                "Une alerte de bénéficiaire effectif non identifié reste ouverte pour {$verification->company_name}.",
                CheckSeverity::Critical,
                $verification->company_name,
            );
        }

        $uboRequired = $this->stageRequires('ubo_required_stages', $stage);
        $uboCheckpoint = $this->stageRequires('ubo_checkpoint_stages', $stage);

        if (! $uboRequired && ! $uboCheckpoint) {
            return $issues;
        }

        $qualifiedUbos = $verification->beneficialOwners()
            ->where('meets_statutory_threshold', true);

        $hasVerifiedQualifiedUbo = (clone $qualifiedUbos)
            ->where('is_verified', true)
            ->exists();

        if (! $hasVerifiedQualifiedUbo) {
            $issues[] = new ComplianceGateIssue(
                'ubo_not_verified',
                "Aucun bénéficiaire effectif qualifié de {$verification->company_name} n'est vérifié.",
                CheckSeverity::Critical,
                $verification->company_name,
            );
        }

        return $issues;
    }

    private function stageRequires(string $configKey, string $stage): bool
    {
        $stages = config('compliance.gates.'.$configKey, []);

        if (is_string($stages)) {
            $stages = array_filter(array_map('trim', explode(',', $stages)));
        }

        return is_array($stages) && in_array($stage, $stages, true);
    }

    private function hasComplianceFootprint(User $user): bool
    {
        $profile = $user->kycProfile()->first();

        if ($profile !== null && $profile->status !== KycStatus::Draft) {
            return true;
        }

        return $this->userComplianceCaseIds($user) !== [];
    }

    private function hasBlockedCase(User $user): bool
    {
        return ComplianceCase::query()
            ->whereIn('id', $this->userComplianceCaseIds($user))
            ->whereIn('status', [
                ComplianceCaseStatus::Blocked->value,
                ComplianceCaseStatus::Suspended->value,
            ])
            ->exists();
    }

    private function hasOpenBlockingAlertForUser(User $user): bool
    {
        $caseIds = $this->userComplianceCaseIds($user);

        if ($caseIds === []) {
            return false;
        }

        return AmlAlert::query()
            ->whereIn('compliance_case_id', $caseIds)
            ->whereIn('status', $this->openAlertStatuses())
            ->where(function ($query) {
                $query->whereIn('alert_type', $this->sanctionsAlertTypes())
                    ->orWhere('severity', AmlRiskLevel::Critical->value);
            })
            ->exists();
    }

    private function caseHasBlockingAlert(ComplianceCase $case): bool
    {
        return $case->alerts()
            ->whereIn('status', $this->openAlertStatuses())
            ->where(function ($query) {
                $query->whereIn('alert_type', $this->sanctionsAlertTypes())
                    ->orWhere('severity', AmlRiskLevel::Critical->value);
            })
            ->exists();
    }

    /**
     * @return list<int>
     */
    private function userComplianceCaseIds(User $user): array
    {
        return ComplianceCase::query()
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere(function ($subjectQuery) use ($user) {
                        $subjectQuery->where('subject_type', User::class)
                            ->where('subject_id', $user->id);
                    });
            })
            ->pluck('id')
            ->all();
    }

    /**
     * @return Collection<int, ComplianceCase>
     */
    private function companyComplianceCases(Company $company): Collection
    {
        $caseIds = ComplianceCase::query()
            ->where('subject_type', Company::class)
            ->where('subject_id', $company->id)
            ->pluck('id');

        $verificationCaseId = $company->latestVerification()->first()?->compliance_case_id;

        if ($verificationCaseId !== null) {
            $caseIds->push($verificationCaseId);
        }

        return ComplianceCase::query()
            ->whereIn('id', $caseIds->unique()->all())
            ->get();
    }

    /**
     * @return list<string>
     */
    private function openAlertStatuses(): array
    {
        return [
            AmlAlertStatus::Open->value,
            AmlAlertStatus::UnderInvestigation->value,
            AmlAlertStatus::Escalated->value,
        ];
    }

    /**
     * @return list<string>
     */
    private function sanctionsAlertTypes(): array
    {
        return [
            AmlAlertType::SanctionsMatch->value,
            AmlAlertType::TargetedFinancialSanctions->value,
        ];
    }
}
