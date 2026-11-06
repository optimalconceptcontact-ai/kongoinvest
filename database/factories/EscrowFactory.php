<?php

namespace Database\Factories;

use App\Enums\EscrowStatus;
use App\Models\Contract;
use App\Models\DealRoom;
use App\Models\Escrow;
use Illuminate\Database\Eloquent\Factories\Factory;

class EscrowFactory extends Factory
{
    protected $model = Escrow::class;

    public function definition(): array
    {
        $dealRoom = DealRoom::factory()->create();
        $contract = Contract::factory()->create([
            'deal_room_id' => $dealRoom->id,
            'status' => 'accepted',
        ]);

        return [
            'deal_room_id' => $dealRoom->id,
            'contract_id' => $contract->id,
            'investor_user_id' => $dealRoom->investor_user_id,
            'entrepreneur_user_id' => $dealRoom->entrepreneur_user_id,
            'amount_minor' => $contract->amount_minor,
            'currency' => $contract->currency,
            'status' => EscrowStatus::Pending->value,
        ];
    }

    public function held(): static
    {
        return $this->state(fn () => ['status' => EscrowStatus::Held->value, 'funded_at' => now()]);
    }
}
