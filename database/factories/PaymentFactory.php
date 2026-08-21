<?php

namespace Database\Factories;

use App\Enums\PaymentProviderName;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    protected $model = \App\Models\Payment::class;

    public function definition(): array
    {
        return [
            'payer_id' => \App\Models\User::factory(),
            'purpose' => PaymentPurpose::Subscription->value,
            'provider' => PaymentProviderName::Stripe->value,
            'amount_minor' => 2900,
            'currency' => 'USD',
            'status' => PaymentStatus::Pending->value,
            'idempotency_key' => (string) Str::uuid(),
        ];
    }
}
