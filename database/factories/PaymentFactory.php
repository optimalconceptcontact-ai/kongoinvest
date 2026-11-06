<?php

namespace Database\Factories;

use App\Enums\PaymentProviderName;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'payer_id' => User::factory(),
            'purpose' => PaymentPurpose::Subscription->value,
            'provider' => PaymentProviderName::Stripe->value,
            'amount_minor' => 2900,
            'currency' => 'USD',
            'status' => PaymentStatus::Pending->value,
            'idempotency_key' => (string) Str::uuid(),
        ];
    }
}
