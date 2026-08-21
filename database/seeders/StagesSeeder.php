<?php

namespace Database\Seeders;

use App\Models\Stage;
use Illuminate\Database\Seeder;

class StagesSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            ['key' => 'idea', 'name' => 'Idée'],
            ['key' => 'pre_seed', 'name' => 'Pré-amorçage'],
            ['key' => 'seed', 'name' => 'Amorçage'],
            ['key' => 'early_stage', 'name' => 'Démarrage'],
            ['key' => 'growth', 'name' => 'Croissance'],
            ['key' => 'expansion', 'name' => 'Expansion'],
            ['key' => 'established', 'name' => 'Établi'],
        ];

        foreach ($stages as $i => $stage) {
            Stage::firstOrCreate(
                ['key' => $stage['key']],
                ['name' => $stage['name'], 'order' => $i, 'is_active' => true],
            );
        }
    }
}
