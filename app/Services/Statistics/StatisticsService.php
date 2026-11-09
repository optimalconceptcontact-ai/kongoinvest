<?php

namespace App\Services\Statistics;

use App\Models\User;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

abstract class StatisticsService
{
    public function __construct(protected TimeSeriesBuilder $series) {}

    abstract protected function title(): string;

    abstract protected function description(): string;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    abstract public function kpis(StatisticsPeriod $period, array $filters): array;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    abstract public function charts(StatisticsPeriod $period, array $filters): array;

    /** @return array<int, array<string, mixed>> */
    abstract public function tableColumns(): array;

    /** @param  array<string, mixed>  $filters */
    abstract public function paginatedTable(array $filters): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    abstract public function allTableRows(array $filters): Collection;

    /**
     * Single report contract shared by the page and every export: both
     * call this method, so a file can never disagree with the screen.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function report(StatisticsPeriod $period, array $filters, array $rows = [], bool $withRows = false): StatisticsReport
    {
        if ($withRows) {
            $rows = $this->allTableRows($filters)->all();
        }

        return new StatisticsReport(
            key: static::KEY,
            title: $this->title(),
            description: $this->description(),
            period: $period->toArray(),
            filters: $filters,
            filterLabels: $this->filterLabels($filters),
            kpis: $this->kpis($period, $filters),
            charts: $this->charts($period, $filters),
            tableColumns: $this->tableColumns(),
            tableRows: $rows,
            generatedAt: CarbonImmutable::now(),
        );
    }

    /** @param  array<string, mixed>  $filters */
    protected function filterLabels(array $filters): array
    {
        return [];
    }

    /**
     * Apply an inclusive [start, end] filter. The period carries UTC bounds
     * so this works regardless of the analytics timezone.
     */
    protected function applyPeriod(Builder $query, string $column, StatisticsPeriod $period): Builder
    {
        return $query->whereBetween($column, [$period->startUtc(), $period->endUtc()]);
    }

    protected function applyPreviousPeriod(Builder $query, string $column, StatisticsPeriod $period): Builder
    {
        return $query->whereBetween($column, [$period->previousStartUtc(), $period->previousEndUtc()]);
    }

    /**
     * Percentage change against the previous period. Null means "n/a"
     * because the previous value is zero (or unavailable) - never Infinity.
     */
    protected function variation(int|float|null $current, int|float|null $previous): ?float
    {
        if ($previous === null || (float) $previous === 0.0) {
            return null;
        }

        return round((((float) $current - (float) $previous) / (float) $previous) * 100, 1);
    }

    /**
     * @param  Builder  $query  already constrained by the caller
     * @return array<string, int>
     */
    protected function totalsByCurrency(Builder $query, string $amountColumn = 'amount_minor', string $currencyColumn = 'currency'): array
    {
        $wrappedAmount = $query->getQuery()->getGrammar()->wrap($amountColumn);
        $wrappedCurrency = $query->getQuery()->getGrammar()->wrap($currencyColumn);

        return $query
            ->selectRaw("{$wrappedCurrency} as currency, coalesce(sum({$wrappedAmount}), 0) as total")
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    protected function applySearch(Builder $query, ?string $search, array $columns): Builder
    {
        if ($search === null || trim($search) === '') {
            return $query;
        }

        $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($search)).'%';

        return $query->where(function (Builder $query) use ($columns, $term) {
            foreach ($columns as $column) {
                $query->orWhere($column, 'like', $term);
            }
        });
    }

    /**
     * Server-side pagination with a strict sort whitelist.
     *
     * @param  array<int, string>  $sortable
     */
    protected function paginate(
        Builder $query,
        array $filters,
        array $sortable,
        string $defaultSort = 'created_at',
        int $defaultPerPage = 25,
    ): LengthAwarePaginator {
        $sort = (string) ($filters['sort'] ?? $defaultSort);
        $sort = in_array($sort, $sortable, true) ? $sort : $defaultSort;

        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = (int) ($filters['per_page'] ?? $defaultPerPage);
        $perPage = max(5, min($perPage, 100));

        return $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Cache an aggregation only when a TTL is configured. The key includes
     * the period, filters and the caller's permission fingerprint so a
     * future role with narrower access never reuses another role's result.
     */
    protected function remember(string $scope, StatisticsPeriod $period, array $filters, Closure $callback): mixed
    {
        $ttl = (int) config('statistics.cache_ttl_seconds', 0);

        if ($ttl <= 0) {
            return $callback();
        }

        $user = auth()->user();
        $key = 'statistics:'.md5(json_encode([
            'scope' => $scope,
            'period' => $period->toArray(),
            'filters' => $filters,
            'roles' => $user?->getRoleNames()->sort()->values()->all() ?? [],
            'permissions' => $user?->getAllPermissions()->pluck('name')->sort()->values()->all() ?? [],
        ]));

        return Cache::remember($key, $ttl, $callback);
    }

    /** @return array<string, mixed> */
    protected function kpi(
        string $key,
        string $label,
        int|float $value,
        string $format,
        ?float $variation = null,
        ?string $variationLabel = null,
        ?string $hint = null,
        ?string $currency = null,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'format' => $format,
            'variation' => $variation,
            'variation_label' => $variationLabel,
            'hint' => $hint,
            'currency' => $currency,
        ];
    }

    /**
     * One KPI per currency. Multi-currency totals are never summed.
     *
     * @param  array<string, int>  $totals  minor units keyed by currency
     * @param  array<string, float|null>  $variations  keyed by currency
     * @return array<int, array<string, mixed>>
     */
    protected function currencyKpis(
        string $keyPrefix,
        string $label,
        array $totals,
        array $variations = [],
        ?string $variationLabel = null,
        ?string $hint = null,
    ): array {
        $normalizedTotals = [];

        foreach ($totals as $currency => $minor) {
            $normalizedTotals[strtoupper((string) $currency)] = (int) $minor;
        }

        $normalizedVariations = [];

        foreach ($variations as $currency => $variation) {
            $normalizedVariations[strtoupper((string) $currency)] = $variation;
        }

        $codes = array_values(array_unique(array_merge(
            array_map('strtoupper', (array) config('statistics.currencies', ['USD', 'CDF'])),
            array_keys($normalizedTotals),
        )));

        $kpis = [];

        foreach ($codes as $code) {
            $kpis[] = $this->kpi(
                $keyPrefix.'_'.strtolower($code),
                $label.' ('.$code.')',
                round(($normalizedTotals[$code] ?? 0) / 100, 2),
                'currency',
                $normalizedVariations[$code] ?? null,
                $variationLabel,
                $hint,
                $code,
            );
        }

        return $kpis;
    }

    /**
     * @param  array<int, array{name: string, value: int|float, color?: string}>  $items
     * @return array<string, mixed>
     */
    protected function pieChart(string $key, string $title, string $description, array $items): array
    {
        $data = [];

        foreach (array_values($items) as $index => $item) {
            if ((float) ($item['value'] ?? 0) <= 0) {
                continue;
            }

            $data[] = [
                'name' => (string) $item['name'],
                'value' => $item['value'],
                'color' => $item['color'] ?? 'chart-'.(($index % 5) + 1),
            ];
        }

        return [
            'key' => $key,
            'type' => 'pie',
            'title' => $title,
            'description' => $description,
            'data' => $data,
            'series' => [],
        ];
    }

    /**
     * Turn a raw count map into pie-chart items, resolving enum labels and
     * sorting by descending volume.
     *
     * @param  array<string, int|float>  $counts
     * @param  array<string, string>  $labels
     * @return array<int, array{name: string, value: int|float, color?: string}>
     */
    protected function pieItems(array $counts, array $labels = [], ?int $limit = null): array
    {
        arsort($counts);

        if ($limit !== null) {
            $counts = array_slice($counts, 0, $limit, true);
        }

        $items = [];

        foreach ($counts as $key => $count) {
            $items[] = [
                'name' => $labels[$key] ?? (string) $key,
                'value' => $count,
            ];
        }

        return $items;
    }

    /** Ratio as a percentage, rounded to one decimal. Zero denominators yield 0. */
    protected function rate(int|float $numerator, int|float $denominator): float
    {
        if ((float) $denominator === 0.0) {
            return 0.0;
        }

        return round(((float) $numerator / (float) $denominator) * 100, 1);
    }

    /**
     * @param  array<string, array<int, array{bucket: string, label: string, value: int|float}>>  $seriesByKey
     * @param  array<string, array{label: string, color: string}>  $definitions
     * @return array<string, mixed>
     */
    protected function lineChart(
        string $key,
        string $type,
        string $title,
        string $description,
        array $seriesByKey,
        array $definitions,
        StatisticsPeriod $period,
    ): array {
        return [
            'key' => $key,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'data' => $this->series->merge($seriesByKey, $period),
            'series' => collect($definitions)
                ->map(fn (array $definition, string $seriesKey) => [
                    'key' => $seriesKey,
                    'label' => $definition['label'],
                    'color' => $definition['color'],
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Count rows per time bucket. The caller supplies an already filtered
     * query; the period constraint is applied on $column.
     *
     * @return array<int, array{bucket: string, label: string, value: int|float}>
     */
    protected function bucketCounts(Builder $query, string $column, StatisticsPeriod $period): array
    {
        $expression = $this->series->dateExpression($query->getQuery(), $column, $period);

        $rows = $this->applyPeriod(clone $query, $column, $period)
            ->selectRaw("{$expression} as bucket, count(*) as total")
            ->groupBy('bucket')
            ->get()
            ->toArray();

        return $this->series->fill($rows, $period);
    }

    /**
     * Sum an amount per time bucket, optionally converting minor units to
     * major units. Multi-currency callers must filter one currency first.
     *
     * @return array<int, array{bucket: string, label: string, value: int|float}>
     */
    protected function bucketSums(
        Builder $query,
        string $dateColumn,
        string $amountColumn,
        StatisticsPeriod $period,
        float $divisor = 1.0,
    ): array {
        $expression = $this->series->dateExpression($query->getQuery(), $dateColumn, $period);
        $wrapped = $query->getQuery()->getGrammar()->wrap($amountColumn);

        $rows = $this->applyPeriod(clone $query, $dateColumn, $period)
            ->selectRaw("{$expression} as bucket, coalesce(sum({$wrapped}), 0) as total")
            ->groupBy('bucket')
            ->get()
            ->toArray();

        $series = $this->series->fill($rows, $period);

        if ($divisor !== 1.0) {
            foreach ($series as $index => $point) {
                $series[$index]['value'] = round($point['value'] / $divisor, 2);
            }
        }

        return $series;
    }

    /** Activity within a period, based on last_seen or audit events. */
    protected function activeInPeriod(Builder $query, StatisticsPeriod $period, bool $previous = false): Builder
    {
        $start = $previous ? $period->previousStartUtc() : $period->startUtc();
        $end = $previous ? $period->previousEndUtc() : $period->endUtc();

        return $query->where(function (Builder $query) use ($start, $end) {
            $query->whereBetween('last_active_at', [$start, $end])
                ->orWhereHas('auditLogs', fn (Builder $logs) => $logs->whereBetween('created_at', [$start, $end]));
        });
    }

    /** Stock "active" filter used by account-status columns and filters. */
    protected function applyAccountStatus(Builder $query, string $status): void
    {
        $since = CarbonImmutable::now()->subDays((int) config('statistics.activity_window_days', 30));

        if ($status === 'active') {
            $query->where(function (Builder $query) use ($since) {
                $query->where('last_active_at', '>=', $since)
                    ->orWhereHas('auditLogs', fn (Builder $logs) => $logs->where('created_at', '>=', $since));
            });

            return;
        }

        $query->where(function (Builder $query) use ($since) {
            $query->where(function (Builder $query) use ($since) {
                $query->whereNull('last_active_at')->orWhere('last_active_at', '<', $since);
            })->whereDoesntHave('auditLogs', fn (Builder $logs) => $logs->where('created_at', '>=', $since));
        });
    }

    /** Latest of the persisted last-activity timestamp and the audit trail. */
    protected function latestActivity(User $user): ?CarbonImmutable
    {
        $dates = array_filter([
            $user->last_active_at,
            $user->getAttribute('last_audit_at'),
        ]);

        $latest = null;

        foreach ($dates as $date) {
            $value = $date instanceof \DateTimeInterface
                ? CarbonImmutable::instance($date)
                : CarbonImmutable::parse((string) $date);

            if ($latest === null || $value->greaterThan($latest)) {
                $latest = $value;
            }
        }

        return $latest;
    }

    /** Active/inactive label from a resolved last-activity timestamp. */
    protected function activityLabel(?CarbonImmutable $lastActive): string
    {
        if ($lastActive === null) {
            return 'Inactif';
        }

        $window = CarbonImmutable::now()->subDays((int) config('statistics.activity_window_days', 30));

        return $lastActive->greaterThanOrEqualTo($window) ? 'Actif' : 'Inactif';
    }
}
