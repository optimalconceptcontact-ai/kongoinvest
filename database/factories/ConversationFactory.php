<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\DealRoom;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'deal_room_id' => DealRoom::factory(),
        ];
    }
}
