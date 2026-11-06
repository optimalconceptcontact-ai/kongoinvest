<?php

namespace App\Services\Ai;

use App\Actions\Contracts\GenerateContract;
use App\Contracts\ContractDraftProviderInterface;
use App\Enums\AiInteractionType;
use App\Enums\AiRequestStatus;
use App\Enums\FundingType;
use App\Models\Contract;
use App\Models\DealRoom;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

/**
 * Only the investor may call this (ContractPolicy::generate) — the AI
 * drafts the contract itself from the deal room's conversation, the
 * investment type the investor picked among the entrepreneur's 3
 * declared options, and the project's funding data. Same
 * quota-check -> provider call -> usage record -> persist shape as
 * ProjectAnalysisService.
 */
class ContractDraftService
{
    public function __construct(
        private ContractDraftProviderInterface $provider,
        private GenerateContract $generateContract,
        private AiUsageService $usage,
        private AuditLogger $audit,
    ) {}

    public function generate(User $investor, DealRoom $dealRoom, FundingType $investmentType): Contract
    {
        $this->usage->assertWithinQuota($investor);

        $result = $this->provider->draft($dealRoom, $investmentType, $this->buildTranscript($dealRoom));

        if ($result === null) {
            $this->usage->record($investor, AiInteractionType::ContractGeneration, AiRequestStatus::Failed, $dealRoom->project);

            throw ValidationException::withMessages([
                'contract' => 'La génération du contrat par IA a échoué. Réessayez dans quelques instants.',
            ]);
        }

        $this->usage->record($investor, AiInteractionType::ContractGeneration, AiRequestStatus::Completed, $dealRoom->project, config('ai.features.provider'), $result->tokensUsed);

        $contract = $this->generateContract->handle($investor, $dealRoom, [
            'investment_type' => $result->investmentType,
            'amount_minor' => $result->amountMinor,
            'currency' => $result->currency,
            'conditions' => $this->formatConditions($result),
            'deadline' => $result->deadline,
        ]);

        $this->audit->log($investor, 'contract.ai_generated', $contract, ['deal_room_id' => $dealRoom->id, 'tokens_used' => $result->tokensUsed]);

        return $contract;
    }

    private function buildTranscript(DealRoom $dealRoom): string
    {
        $messages = $dealRoom->conversation?->messages ?? collect();

        if ($messages->isEmpty()) {
            return "(Aucun message échangé pour l'instant dans ce Deal Room.)";
        }

        // Party-supplied content is untrusted: each message is delimited,
        // labeled with its author, and capped so one side can neither
        // smuggle instructions nor flood the agent context. Only the most
        // recent messages are kept.
        return $messages
            ->take(-50)
            ->map(function ($m) use ($dealRoom) {
                $role = $m->sender_id === $dealRoom->entrepreneur_user_id ? 'Entrepreneur' : 'Investisseur';
                $body = mb_substr((string) $m->body, 0, 1000);

                return "[Message {$role} ({$m->sender->name}) — propos non vérifiés]\n{$body}";
            })
            ->implode("\n---\n");
    }

    private function formatConditions(ContractDraftResult $result): string
    {
        $bullets = fn (array $items) => $items === [] ? 'Aucune précisée.' : collect($items)->map(fn ($i) => "- {$i}")->implode("\n");

        $sections = [
            'Résumé' => $result->summary,
            "Obligations de l'entrepreneur" => $bullets($result->obligationsEntrepreneur),
            "Obligations de l'investisseur" => $bullets($result->obligationsInvestor),
            'Conditions préalables' => $bullets($result->conditionsPrecedent),
            'Jalons' => $bullets($result->milestones),
            'Clauses de résiliation' => $result->terminationClauses,
            'Règlement des différends' => $result->disputeResolution,
            'Droit applicable' => $result->governingLaw,
        ];

        return collect($sections)
            ->map(fn ($value, $label) => "## {$label}\n{$value}")
            ->implode("\n\n");
    }
}
