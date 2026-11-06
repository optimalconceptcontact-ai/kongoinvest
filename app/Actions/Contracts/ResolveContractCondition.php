<?php

namespace App\Actions\Contracts;

use App\Enums\ContractConditionStatus;
use App\Enums\WorkspaceStatus;
use App\Models\ContractCondition;
use App\Models\InvestmentContractWorkspace;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Conduite d'une condition à son terme (prompt §9) : conduite à
 * terme `fulfilled` (horodaté), exécution impossible `not_fulfilled`
 * ou renonciation explicite `waived`. Le statut dénormalisé du
 * document... n'existe pas ici : chaque résolution est horodatée,
 * attribuée — et une condition déjà conduite ne se renie plus.
 */
class ResolveContractCondition
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $user, InvestmentContractWorkspace $workspace, ContractCondition $condition, array $input = []): ContractCondition
    {
        if ($condition->investment_contract_workspace_id !== $workspace->id) {
            throw ValidationException::withMessages([
                'condition' => 'Cette condition n\'appartient pas à cette opération.',
            ]);
        }

        if (! $workspace->isParticipant($user)) {
            throw ValidationException::withMessages([
                'workspace' => 'Seuls les participants de l\'opération conduisent les conditions.',
            ]);
        }

        if ($workspace->isClosed() || $workspace->status === WorkspaceStatus::Cancelled) {
            throw ValidationException::withMessages([
                'workspace' => 'L\'opération est clôturée ou annulée : plus de résolution possible.',
            ]);
        }

        $data = Validator::make($input, [
            'decision' => ['required', Rule::in(
                collect(ContractConditionStatus::cases())
                    ->reject(fn ($status) => $status === ContractConditionStatus::Pending)
                    ->map(fn ($status) => $status->value)
                    ->values()
                    ->all()
            )],
        ])->validate();

        if ($condition->status !== ContractConditionStatus::Pending) {
            throw ValidationException::withMessages([
                'condition' => 'Cette condition est déjà conduite ('.$condition->status->label().') : elle ne se renie plus.',
            ]);
        }

        $decision = ContractConditionStatus::from($data['decision']);

        $condition->forceFill([
            'status' => $decision->value,
            'verified_by_user_id' => $user->id,
            'fulfilled_at' => $decision === ContractConditionStatus::Fulfilled ? now() : null,
        ])->save();

        $workspace->forceFill(['last_activity_at' => now()])->save();

        $this->audit->log($user, 'workspace.condition_resolved', $condition, [
            'workspace_id' => $workspace->id,
            'condition_id' => $condition->id,
            'decision' => $decision->value,
        ]);

        return $condition->fresh();
    }
}
