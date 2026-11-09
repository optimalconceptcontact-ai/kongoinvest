<?php

namespace App\Http\Requests\Admin;

use App\Enums\StatisticsPreset;
use App\Services\Statistics\PeriodResolver;
use App\Services\Statistics\StatisticsPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class StatisticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return array_merge([
            'period' => ['sometimes', Rule::enum(StatisticsPreset::class)],
            'month' => ['sometimes', 'nullable', 'integer', 'between:1,12'],
            'year' => ['sometimes', 'nullable', 'integer', 'between:2000,'.(now()->year + 1)],
            'date_from' => ['nullable', 'date_format:Y-m-d', 'required_if:period,custom'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from', 'required_if:period,custom'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:5,100'],
            'sort' => ['sometimes', 'nullable', 'string', Rule::in($this->sortableColumns())],
            'direction' => ['sometimes', 'nullable', Rule::in(['asc', 'desc'])],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
        ], $this->filterRules());
    }

    /**
     * Page-specific filter rules. Each analytics page validates only the
     * filters it actually exposes; unknown query parameters are ignored.
     *
     * @return array<string, mixed>
     */
    protected function filterRules(): array
    {
        return [];
    }

    /**
     * Validated keys copied into the filters payload.
     *
     * @return array<int, string>
     */
    protected function filterKeys(): array
    {
        return ['search', 'sort', 'direction', 'per_page'];
    }

    /** @return array<int, string> */
    protected function sortableColumns(): array
    {
        return ['created_at'];
    }

    public function period(): StatisticsPeriod
    {
        return app(PeriodResolver::class)->resolve($this->safe()->all());
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        return $this->safe()->only($this->filterKeys());
    }

    /** @return array<string, mixed> */
    public function periodInput(): array
    {
        return $this->safe()->only(['period', 'month', 'year', 'date_from', 'date_to']);
    }
}
