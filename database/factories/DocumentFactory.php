<?php

namespace Database\Factories;

use App\Enums\DocumentVisibility;
use App\Models\DealRoom;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'deal_room_id' => DealRoom::factory(),
            'owner_id' => User::factory(),
            'title' => fake()->words(3, true),
            'visibility' => DocumentVisibility::Private->value,
        ];
    }
}
