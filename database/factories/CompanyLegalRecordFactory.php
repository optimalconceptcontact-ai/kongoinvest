<?php

namespace Database\Factories;

use App\Enums\LegalForm;
use App\Models\Company;
use App\Models\CompanyLegalRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyLegalRecordFactory extends Factory
{
    protected $model = CompanyLegalRecord::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'legal_form' => LegalForm::Sarlu->value,
            'rccm' => 'CD/'.$this->faker->unique()->numberBetween(1000, 9999).'/KIN-RCCM/'.fake()->randomNumber(5),
            'registered_office' => 'Kinshasa, RDC',
            'corporate_purpose' => fake()->sentence(),
            'capital_minor' => 30_000_000,
            'capital_currency' => 'USD',
            'share_count' => 300,
            'nominal_value_minor' => 100_000,
            'verification' => 'unverified',
            'is_current' => true,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => ['verification' => 'verified']);
    }

    public function sarl(int $shareCount = 300): static
    {
        return $this->state(fn () => ['legal_form' => LegalForm::Sarl->value, 'share_count' => $shareCount]);
    }

    public function sas(): static
    {
        return $this->state(fn () => ['legal_form' => LegalForm::Sas->value]);
    }

    public function sa(): static
    {
        return $this->state(fn () => ['legal_form' => LegalForm::Sa->value]);
    }
}
