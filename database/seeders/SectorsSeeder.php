<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

class SectorsSeeder extends Seeder
{
    public function run(): void
    {
        $sectors = [
            ['name' => 'Agriculture', 'icon' => 'RiPlantLine'],
            ['name' => 'Énergie', 'icon' => 'RiFlashlightLine'],
            ['name' => 'Technologie', 'icon' => 'RiCodeSSlashLine'],
            ['name' => 'Santé', 'icon' => 'RiHeartPulseLine'],
            ['name' => 'Infrastructure', 'icon' => 'RiBuilding2Line'],
            ['name' => 'Mines', 'icon' => 'RiHammerLine'],
            ['name' => 'Logistique', 'icon' => 'RiTruckLine'],
            ['name' => 'Éducation', 'icon' => 'RiBookOpenLine'],
            ['name' => 'Services financiers', 'icon' => 'RiBankLine'],
            ['name' => 'Industrie manufacturière', 'icon' => 'RiToolsLine'],
            ['name' => 'Tourisme', 'icon' => 'RiMapPinLine'],
        ];

        foreach ($sectors as $i => $sector) {
            Sector::firstOrCreate(
                ['name' => $sector['name']],
                ['icon' => $sector['icon'], 'order' => $i, 'is_active' => true],
            );
        }
    }
}
