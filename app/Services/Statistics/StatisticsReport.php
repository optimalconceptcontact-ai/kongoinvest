<?php

namespace App\Services\Statistics;

use Carbon\CarbonImmutable;

/**
 * Single report contract shared by the Inertia page and every export.
 * Both paths must be built from the same service call, so a PDF/Excel/CSV
 * can never disagree with what the administrator sees on screen.
 */
final class StatisticsReport
{
    /**
     * @param  array<string, mixed>  $period
     * @param  array<string, mixed>  $filters
     * @param  array<int, array<string, mixed>>  $kpis
     * @param  array<int, array<string, mixed>>  $charts
     * @param  array<int, array<string, mixed>>  $tableColumns
     * @param  array<int, array<string, mixed>>  $tableRows
     */
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly string $description,
        public readonly array $period,
        public readonly array $filters,
        public readonly array $filterLabels,
        public readonly array $kpis,
        public readonly array $charts,
        public readonly array $tableColumns,
        public readonly array $tableRows,
        public readonly CarbonImmutable $generatedAt,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'title' => $this->title,
            'description' => $this->description,
            'period' => $this->period,
            'filters' => $this->filters,
            'filter_labels' => $this->filterLabels,
            'kpis' => $this->kpis,
            'charts' => $this->charts,
            'table' => [
                'columns' => $this->tableColumns,
                'rows' => $this->tableRows,
                'total' => count($this->tableRows),
            ],
            'generated_at' => $this->generatedAt->toIso8601String(),
        ];
    }
}
