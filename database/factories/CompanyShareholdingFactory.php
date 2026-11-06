<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyShareholding;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyShareholdingFactory extends Factory
{
    protected $model = CompanyShareholding::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'holder_type' => 'individual',
            'holder_name' => fake()->name(),
            'share_count' => 100,
            'percentage_bps' => 10_000,
            'verification' => 'unverified',
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => ['verification' => 'verified']);
    }

    public function founder(): static
    {
        return $this->state(fn () => ['is_founder' => true, 'is_beneficial_owner' => true]);
    }
}
