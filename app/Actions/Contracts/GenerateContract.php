<?php

namespace App\Actions\Contracts;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\DealRoom;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class GenerateContract
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array{investment_type: string, amount_minor: int, currency: string, conditions?: string, deadline?: string}  $terms
     */
    public function handle(User $generator, DealRoom $dealRoom, array $terms): Contract
    {
        return DB::transaction(function () use ($generator, $dealRoom, $terms) {
            // Contract::$fillable is deliberately empty — create() would
            // silently drop every field, so forceCreate() bypasses that
            // protection for this trusted, server-controlled call.
            $contract = $dealRoom->contracts()->forceCreate([
                'generated_by_user_id' => $generator->id,
                'status' => ContractStatus::Generated->value,
                'investment_type' => $terms['investment_type'],
                'amount_minor' => $terms['amount_minor'],
                'currency' => $terms['currency'],
                'terms' => $terms,
                'template_version' => 'v1',
            ]);

            // Spec §128: the accepted version must be an immutable copy.
            // Rather than only snapshotting on acceptance, every
            // generation writes a version — so even a re-generated draft
            // has full history, and "the version that was accepted" is
            // never ambiguous.
            // ContractVersion::$fillable is deliberately empty too.
            $contract->versions()->forceCreate([
                'version' => 1,
                'content' => $this->buildContent($dealRoom, $terms),
                'created_by_user_id' => $generator->id,
            ]);

            $this->audit->log($generator, 'contract.generated', $contract, ['deal_room_id' => $dealRoom->id]);

            return $contract->fresh();
        });
    }

    /**
     * Structured content for the generated document. Spec §47 is
     * explicit: this is a structured template, not a legally binding
     * document created by an AI or algorithm alone — the disclaimer
     * embedded here is not optional and is also shown verbatim in the
     * React contract view.
     */
    private function buildContent(DealRoom $dealRoom, array $terms): array
    {
        return [
            'disclaimer' => 'Ce document est généré automatiquement à partir des conditions du deal et ne constitue pas un contrat juridiquement validé. Une revue et adaptation par un professionnel du droit est nécessaire avant toute signature engageante (spec §47).',
            'parties' => [
                'entrepreneur' => $dealRoom->entrepreneur->name,
                'investor' => $dealRoom->investor->name,
            ],
            'project' => $dealRoom->project->title,
            'investment_type' => $terms['investment_type'],
            'amount_minor' => $terms['amount_minor'],
            'currency' => $terms['currency'],
            'conditions' => $terms['conditions'] ?? null,
            'deadline' => $terms['deadline'] ?? null,
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
