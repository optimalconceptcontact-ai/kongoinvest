<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = \App\Models\Message::class;

    public function definition(): array
    {
        return [
            'conversation_id' => \App\Models\Conversation::factory(),
            'sender_id' => \App\Models\User::factory(),
            'body' => fake()->sentence(),
        ];
    }
}
