<?php

namespace App\Http\Requests\Admin;

use App\Enums\DrcProvince;
use App\Enums\ProjectStatus;
use App\Enums\ProjectVisibility;
use Illuminate\Validation\Rule;

class ProjectsStatisticsRequest extends StatisticsRequest
{
    protected function filterRules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', Rule::enum(ProjectStatus::class)],
            'visibility' => ['sometimes', 'nullable', Rule::enum(ProjectVisibility::class)],
            'province' => ['sometimes', 'nullable', Rule::in(array_column(DrcProvince::cases(), 'value'))],
            'sector_id' => ['sometimes', 'nullable', 'integer', 'exists:sectors,id'],
            'stage_id' => ['sometimes', 'nullable', 'integer', 'exists:stages,id'],
            'funding_currency' => ['sometimes', 'nullable', Rule::in(['USD', 'CDF'])],
        ];
    }

    protected function filterKeys(): array
    {
        return [
            'search', 'sort', 'direction', 'per_page',
            'status', 'visibility', 'province', 'sector_id', 'stage_id', 'funding_currency',
        ];
    }

    protected function sortableColumns(): array
    {
        return ['title', 'created_at', 'published_at', 'funding_target_minor'];
    }
}
