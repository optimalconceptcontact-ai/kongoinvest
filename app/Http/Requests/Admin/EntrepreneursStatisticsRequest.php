<?php

namespace App\Http\Requests\Admin;

use App\Enums\DrcProvince;
use Illuminate\Validation\Rule;

class EntrepreneursStatisticsRequest extends StatisticsRequest
{
    protected function filterRules(): array
    {
        return [
            'province' => ['sometimes', 'nullable', Rule::in(array_column(DrcProvince::cases(), 'value'))],
            'sector_id' => ['sometimes', 'nullable', 'integer', 'exists:sectors,id'],
            'stage_id' => ['sometimes', 'nullable', 'integer', 'exists:stages,id'],
            'onboarding' => ['sometimes', 'nullable', Rule::in(['complete', 'incomplete'])],
            'account_status' => ['sometimes', 'nullable', Rule::in(['active', 'inactive'])],
        ];
    }

    protected function filterKeys(): array
    {
        return [
            'search', 'sort', 'direction', 'per_page',
            'province', 'sector_id', 'stage_id', 'onboarding', 'account_status',
        ];
    }

    protected function sortableColumns(): array
    {
        return ['name', 'email', 'created_at', 'last_active_at'];
    }
}
