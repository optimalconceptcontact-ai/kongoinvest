<?php

namespace App\Http\Requests\Admin;

use App\Enums\EscrowStatus;
use Illuminate\Validation\Rule;

class InvestmentsStatisticsRequest extends StatisticsRequest
{
    protected function filterRules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', Rule::enum(EscrowStatus::class)],
            'currency' => ['sometimes', 'nullable', Rule::in(['USD', 'CDF'])],
        ];
    }

    protected function filterKeys(): array
    {
        return ['search', 'sort', 'direction', 'per_page', 'status', 'currency'];
    }

    protected function sortableColumns(): array
    {
        return ['amount_minor', 'funded_at', 'released_at', 'created_at'];
    }
}
