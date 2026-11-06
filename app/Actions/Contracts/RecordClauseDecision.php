<?php

namespace App\Actions\Contracts;

use App\Enums\ClauseDecision;
use App\Enums\ClauseStatus;
use App\Enums\WorkspaceStatus;
use App\Models\ContractClauseApproval;
use App\Models\ContractClauseSelection;
use App\Models\InvestmentContractWorkspace;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Décision d'une partie sur une clause (prompt §8). Une clause
 * n'est JAMAIS convenue qu'en présence d'acceptation des DEUX
 * parties enregistrées dans contract_clause_approvals (append-only)
 * — le statut est DÉRIVÉ du dernier mot de chaque côté, jamais
 * assigné librement (ContractClauseSelection::isAcceptedByBoth()
 * est la règle de fond, le statut normalisé reste de l'affichage).
 *
 * Discipline de l'échange :
 * - la toute première décision vient du côté qui N'A PAS proposé
 *   (accepter sa propre proposition ne ferait pas un accord) ;
 * - ensuite, les décisions alternent : on ne renie jamais deux fois
 *   de suite la même partie (chaque réaction répond à un mot).
 */
class RecordClauseDecision
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $user, InvestmentContractWorkspace $workspace, ContractClauseSelection $selection, array $input = []): ContractClauseSelection
    {
        if ($selection->investment_contract_workspace_id !== $workspace->id) {
            throw ValidationException::withMessages([
                'clause' => 'Cette clause n\'appartient pas à cette opération.',
            ]);
        }

        if (! $workspace->isParticipant($user)) {
            throw ValidationException::withMessages([
                'workspace' => 'Seuls les participants de l\'opération décident des clauses.',
            ]);
        }

        if ($workspace->isClosed() || $workspace->status === WorkspaceStatus::Cancelled) {
            throw ValidationException::withMessages([
                'workspace' => 'L\'opération est clôturée ou annulée : plus de décision possible.',
            ]);
        }

        if ($selection->status === ClauseStatus::AcceptedByBoth || $selection->status === ClauseStatus::Refused) {
            throw ValidationException::withMessages([
                'clause' => 'Cette clause est définitivement '.$this->lexical($selection->status).' : plus de décision possible.',
            ]);
        }

        $data = Validator::make($input, [
            'decision' => ['required', Rule::enum(ClauseDecision::class)],
            'body' => ['sometimes', 'string', 'max:20000'],
            'variant' => ['sometimes', 'string', 'max:80'],
            'comment' => ['sometimes', 'string', 'max:2000'],
        ])->validate();

        $side = $workspace->sideOf($user);

        if ($side === null) {
            throw ValidationException::withMessages([
                'clause' => 'Seuls les fondateurs et investisseurs de l\'opération décident des clauses.',
            ]);
        }

        $this->assertTurnGuard($side, $workspace, $selection);

        ContractClauseApproval::forceCreate([
            'contract_clause_selection_id' => $selection->id,
            'user_id' => $user->id,
            'decision' => $data['decision'],
            'body' => isset($data['body']) ? Str::squish((string) $data['body']) : null,
            'variant' => $data['variant'] ?? null,
            'comment' => $data['comment'] ?? null,
            'decided_at' => now(),
        ]);

        $this->denormalizeDecisions($selection, $workspace);
        $this->deriveStatus($selection, $workspace);

        $workspace->forceFill(['last_activity_at' => now()])->save();

        $this->audit->log($user, 'workspace.clause_decided', $selection, [
            'workspace_id' => $workspace->id,
            'clause_selection_id' => $selection->id,
            'side' => $side,
            'decision' => $data['decision'],
        ]);

        return $selection->fresh();
    }

    private function lexical(ClauseStatus $status): string
    {
        return $status === ClauseStatus::AcceptedByBoth ? 'acceptée' : 'refusée';
    }

    /**
     * Côté contractuel du proposant : l'entrepreneur de la salle
     * est le fondateur, l'investisseur l'investisseur — mapping
     * déduit des ids, jamais libre.
     */
    private function proposedSide(ContractClauseSelection $selection, InvestmentContractWorkspace $workspace): ?string
    {
        if ($selection->proposed_by_user_id === null) {
            $user = null;
        } else {
            $user = User::query()->find($selection->proposed_by_user_id);
        }

        if ($user === null) {
            return null;
        }

        return $workspace->sideOf($user);
    }

    /**
     * Discipline de l'échange (voir en-tête de classe) : la partie
     * qui a proposé ne tranche pas la première, et on n'empile pas
     * deux décisions consécutives du même côté.
     */
    private function assertTurnGuard(string $side, InvestmentContractWorkspace $workspace, ContractClauseSelection $selection): void
    {
        $latestSide = $this->latestSide($selection, $workspace);

        if ($latestSide === null) {
            // Prem décision jamais par le côté proposant.
            if ($this->proposedSide($selection, $workspace) === $side) {
                $label = $side === 'founder' ? 'fondateur' : 'investisseur';

                throw ValidationException::withMessages([
                    'clause' => "C'est votre propre proposition : attendez la position de l'autre partie (vous êtes {$label}).",
                ]);
            }

            return;
        }

        // Décisions suivantes : alternance stricte.
        if ($latestSide === $side) {
            $label = $side === 'founder' ? 'fondateur' : 'investisseur';

            throw ValidationException::withMessages([
                'clause' => "Le dernier mot reste à l'autre partie : les décisions alternent (vous, {$label}, venez de réagir).",
            ]);
        }
    }

    private function latestSide(ContractClauseSelection $selection, InvestmentContractWorkspace $workspace): ?string
    {
        $latest = $selection->approvals()
            ->orderByDesc('decided_at')
            ->orderByDesc('id')
            ->first();

        return $latest === null ? null : $workspace->sideOf($latest->user);
    }

    /**
     * Dernier mot de chaque partie, dénormalisé pour l'affichage —
     * la source de vérité reste contract_clause_approvals.
     */
    private function denormalizeDecisions(ContractClauseSelection $selection, InvestmentContractWorkspace $workspace): void
    {
        $latestFor = fn (string $side): ?ContractClauseApproval => $selection->approvals()
            ->orderByDesc('decided_at')
            ->orderByDesc('id')
            ->get()
            ->first(fn (ContractClauseApproval $approval) => $workspace->sideOf($approval->user) === $side);

        $founder = $latestFor('founder');
        $investor = $latestFor('investor');

        $selection->forceFill([
            'founder_decision' => $founder?->decision->value,
            'founder_decided_at' => $founder?->decided_at,
            'investor_decision' => $investor?->decision->value,
            'investor_decided_at' => $investor?->decided_at,
        ])->save();
    }

    /**
     * Statut dérivé DERNIER MOT chrono-ordonné (decided_at, puis
     * id en cas d'égalité — deux mots dans la même seconde restent
     * ordonnés) : le mot le plus récent caractérise l'état courant,
     * l'acceptation double est la seule clause convenue (§8) et
     * tout refus est terminal.
     */
    private function deriveStatus(ContractClauseSelection $selection, InvestmentContractWorkspace $workspace): void
    {
        // Parcours du plus récent au plus ancien : la première
        // occurrence de chaque côté EST son dernier mot, et l'ordre
        // d'apparition donne le dernier mot global.
        $founderDecision = null;
        $investorDecision = null;
        $laterSide = null;

        $approvals = $selection->approvals()
            ->orderByDesc('decided_at')
            ->orderByDesc('id')
            ->get();

        foreach ($approvals as $approval) {
            $side = $workspace->sideOf($approval->user);

            if ($side === null) {
                continue;
            }

            if ($side === 'founder' && $founderDecision === null) {
                $founderDecision = $approval->decision;
            } elseif ($side === 'investor' && $investorDecision === null) {
                $investorDecision = $approval->decision;
            }

            $laterSide ??= $side;
        }

        if ($founderDecision !== null && $investorDecision !== null
            && $founderDecision === ClauseDecision::Accept
            && $investorDecision === ClauseDecision::Accept) {
            $this->applyStatus($selection, ClauseStatus::AcceptedByBoth);

            return;
        }

        if ($founderDecision === ClauseDecision::Refuse || $investorDecision === ClauseDecision::Refuse) {
            $this->applyStatus($selection, ClauseStatus::Refused);

            return;
        }

        $laterDecision = $laterSide === 'founder' ? $founderDecision : ($laterSide === 'investor' ? $investorDecision : null);

        if ($laterDecision === null) {
            return; // aucun dernier mot : statut conservé
        }

        // Acceptation unilatérale d'un contre-texte : en attente de
        // la confirmation de l'autre partie.
        if ($laterDecision === ClauseDecision::CounterProposal
            || $laterDecision === ClauseDecision::RequestRevision
            || $laterDecision === ClauseDecision::Accept) {
            $this->applyStatus($selection, $laterDecision === ClauseDecision::RequestRevision
                ? ClauseStatus::ToRevise
                : ClauseStatus::InNegotiation);
        }
    }

    private function applyStatus(ContractClauseSelection $selection, ClauseStatus $status): void
    {
        if ($selection->status === $status) {
            return; // déjà caractérisé ainsi : pas de réécriture
        }

        $selection->forceFill(['status' => $status->value])->save();
    }
}
