<?php

namespace Database\Seeders;

use App\Models\MatchingCriteriaWeight;
use App\Services\Matching\MatchingWeightsService;
use Illuminate\Database\Seeder;

class MatchingWeightsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (MatchingWeightsService::defaults() as $criterion => $weight) {
            MatchingCriteriaWeight::updateOrCreate(
                ['criterion' => $criterion],
                ['weight' => $weight],
            );
        }
    }
}
