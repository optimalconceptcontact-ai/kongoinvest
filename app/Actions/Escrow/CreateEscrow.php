<?php

namespace App\Actions\Escrow;

use App\Enums\EscrowStatus;
use App\Models\Contract;
use App\Models\Escrow;
use App\Services\AuditLogger;
use App\Services\Compliance\InvestmentComplianceGateService;
use Illuminate\Validation\ValidationException;

class CreateEscrow
{
    public function __construct(
        private AuditLogger $audit,
        private InvestmentComplianceGateService $compliance,
    ) {}

    public function handle(Contract $contract): Escrow
    {
        if ($contract->status->value !== 'accepted') {
            throw ValidationException::withMessages([
                'contract' => 'Le contrat doit être accepté par les deux parties avant de créer un séquestre.',
            ]);
        }

        if ($contract->escrow()->exists()) {
            throw ValidationException::withMessages([
                'contract' => 'Un séquestre existe déjà pour ce contrat.',
            ]);
        }

        $this->compliance->assertAllows(
            $this->compliance->evaluateContract(
                $contract,
                InvestmentComplianceGateService::STAGE_ESCROW_CREATION,
            ),
            'contract',
        );

        // Escrow::$fillable is deliberately empty — create() would
        // silently drop every field, so forceCreate() bypasses that
        // protection for this trusted, server-controlled call.
        $escrow = $contract->escrow()->forceCreate([
            'deal_room_id' => $contract->deal_room_id,
            'investor_user_id' => $contract->dealRoom->investor_user_id,
            'entrepreneur_user_id' => $contract->dealRoom->entrepreneur_user_id,
            'amount_minor' => $contract->amount_minor,
            'currency' => $contract->currency,
            'status' => EscrowStatus::Pending->value,
        ]);

        $escrow->events()->create(['to_status' => EscrowStatus::Pending->value, 'actor_id' => $contract->generated_by_user_id]);
        $this->audit->log($contract->generatedBy, 'escrow.created', $escrow, ['contract_id' => $contract->id]);

        return $escrow->fresh();
    }
}
