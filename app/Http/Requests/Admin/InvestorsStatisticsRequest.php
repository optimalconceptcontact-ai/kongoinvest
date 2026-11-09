<?php

namespace App\Http\Requests\Admin;

use App\Enums\FundingType;
use App\Enums\InvestmentHorizon;
use App\Enums\InvestorType;
use App\Enums\RiskAppetite;
use Illuminate\Validation\Rule;

class InvestorsStatisticsRequest extends StatisticsRequest
{
    protected function filterRules(): array
    {
        return [
            'country_code' => ['sometimes', 'nullable', 'string', 'size:2'],
            'investor_type' => ['sometimes', 'nullable', Rule::enum(InvestorType::class)],
            'funding_type' => ['sometimes', 'nullable', Rule::enum(FundingType::class)],
            'risk_appetite' => ['sometimes', 'nullable', Rule::enum(RiskAppetite::class)],
            'investment_horizon' => ['sometimes', 'nullable', Rule::enum(InvestmentHorizon::class)],
            'onboarding' => ['sometimes', 'nullable', Rule::in(['complete', 'incomplete'])],
            'account_status' => ['sometimes', 'nullable', Rule::in(['active', 'inactive'])],
        ];
    }

    protected function filterKeys(): array
    {
        return [
            'search', 'sort', 'direction', 'per_page',
            'country_code', 'investor_type', 'funding_type',
            'risk_appetite', 'investment_horizon', 'onboarding', 'account_status',
        ];
    }

    protected function sortableColumns(): array
    {
        return ['name', 'email', 'created_at', 'last_active_at'];
    }
}
