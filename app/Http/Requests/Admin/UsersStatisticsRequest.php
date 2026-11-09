<?php

namespace App\Http\Requests\Admin;

use App\Enums\DrcProvince;
use Illuminate\Validation\Rule;

class UsersStatisticsRequest extends StatisticsRequest
{
    protected function filterRules(): array
    {
        return [
            'account_type' => ['sometimes', 'nullable', Rule::in(['entrepreneur', 'investor', 'admin'])],
            'account_status' => ['sometimes', 'nullable', Rule::in(['active', 'inactive'])],
            'profile_status' => ['sometimes', 'nullable', Rule::in(['complete', 'incomplete'])],
            'province' => ['sometimes', 'nullable', Rule::in(array_column(DrcProvince::cases(), 'value'))],
        ];
    }

    protected function filterKeys(): array
    {
        return [
            'search', 'sort', 'direction', 'per_page',
            'account_type', 'account_status', 'profile_status', 'province',
        ];
    }

    protected function sortableColumns(): array
    {
        return ['name', 'email', 'created_at', 'last_active_at'];
    }
}
