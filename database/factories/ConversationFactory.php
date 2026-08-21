<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    protected $model = \App\Models\Conversation::class;

    public function definition(): array
    {
        return [
            'deal_room_id' => \App\Models\DealRoom::factory(),
        ];
    }
}
