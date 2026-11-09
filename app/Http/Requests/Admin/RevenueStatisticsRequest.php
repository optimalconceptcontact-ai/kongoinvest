<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentProviderName;
use App\Enums\PaymentStatus;
use Illuminate\Validation\Rule;

class RevenueStatisticsRequest extends StatisticsRequest
{
    protected function filterRules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', Rule::enum(PaymentStatus::class)],
            'provider' => ['sometimes', 'nullable', Rule::enum(PaymentProviderName::class)],
            'currency' => ['sometimes', 'nullable', Rule::in(['USD', 'CDF'])],
            'plan_id' => ['sometimes', 'nullable', 'integer', 'exists:subscription_plans,id'],
        ];
    }

    protected function filterKeys(): array
    {
        return ['search', 'sort', 'direction', 'per_page', 'status', 'provider', 'currency', 'plan_id'];
    }

    protected function sortableColumns(): array
    {
        return ['created_at', 'amount_minor', 'status'];
    }
}
