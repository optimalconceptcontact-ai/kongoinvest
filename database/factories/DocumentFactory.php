<?php

namespace Database\Factories;

use App\Enums\DocumentVisibility;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = \App\Models\Document::class;

    public function definition(): array
    {
        return [
            'deal_room_id' => \App\Models\DealRoom::factory(),
            'owner_id' => \App\Models\User::factory(),
            'title' => fake()->words(3, true),
            'visibility' => DocumentVisibility::Private->value,
        ];
    }
}
