<?php

namespace App\Services\Statistics;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;

final class TimeSeriesBuilder
{
    /**
     * @return array<int, array{key: string, label: string}>
     */
    public function buckets(StatisticsPeriod $period): array
    {
        $buckets = [];
        $cursor = $period->start->startOfDay();

        if ($period->granularity === 'month') {
            $cursor = $cursor->startOfMonth();

            while ($cursor->lessThanOrEqualTo($period->end)) {
                $buckets[] = [
                    'key' => $cursor->format('Y-m'),
                    'label' => $cursor->format('m/Y'),
                ];
                $cursor = $cursor->addMonthNoOverflow();
            }

            return $buckets;
        }

        while ($cursor->lessThanOrEqualTo($period->end)) {
            $buckets[] = [
                'key' => $cursor->format('Y-m-d'),
                'label' => $cursor->format('d/m'),
            ];
            $cursor = $cursor->addDay();
        }

        return $buckets;
    }

    /**
     * Database-portable SQL expression that truncates a timestamp to the
     * period's bucket (day or month).
     */
    public function dateExpression(Builder $query, string $column, StatisticsPeriod $period): string
    {
        $driver = $query->getConnection()->getDriverName();
        $wrapped = $query->getGrammar()->wrap($column);

        // Timestamps are stored in UTC. Shift by the analytics timezone
        // offset (Kinshasa is a fixed UTC+2, no DST) before truncating so
        // an event at 00:30 Kinshasa lands in the correct local day
        // bucket instead of the previous UTC day.
        $offset = (int) $period->start->utcOffset();
        $interval = "{$offset} minute".(abs($offset) === 1 ? '' : 's');

        if ($period->granularity === 'month') {
            return match ($driver) {
                'sqlite' => "strftime('%Y-%m', datetime({$wrapped}, '{$interval}'))",
                'mysql', 'mariadb' => "DATE_FORMAT(DATE_ADD({$wrapped}, INTERVAL {$offset} MINUTE), '%Y-%m')",
                'pgsql' => "to_char({$wrapped} + interval '{$interval}', 'YYYY-MM')",
                'sqlsrv' => "FORMAT(DATEADD(minute, {$offset}, {$wrapped}), 'yyyy-MM')",
                default => "DATE_FORMAT(DATE_ADD({$wrapped}, INTERVAL {$offset} MINUTE), '%Y-%m')",
            };
        }

        return match ($driver) {
            'sqlite' => "strftime('%Y-%m-%d', datetime({$wrapped}, '{$interval}'))",
            'mysql', 'mariadb' => "DATE_FORMAT(DATE_ADD({$wrapped}, INTERVAL {$offset} MINUTE), '%Y-%m-%d')",
            'pgsql' => "to_char({$wrapped} + interval '{$interval}', 'YYYY-MM-DD')",
            'sqlsrv' => "CONVERT(varchar(10), DATEADD(minute, {$offset}, {$wrapped}), 23)",
            default => "DATE_FORMAT(DATE_ADD({$wrapped}, INTERVAL {$offset} MINUTE), '%Y-%m-%d')",
        };
    }

    /**
     * Fill every bucket of the period, including empty ones (zero).
     *
     * @param  array<int, array{bucket: string, total: int|float|string}>  $rows
     * @return array<int, array{bucket: string, label: string, value: int|float}>
     */
    public function fill(array $rows, StatisticsPeriod $period): array
    {
        $values = [];

        foreach ($rows as $row) {
            $key = (string) ($row['bucket'] ?? '');
            $values[$key] = ($values[$key] ?? 0) + (float) ($row['total'] ?? 0);
        }

        $series = [];

        foreach ($this->buckets($period) as $bucket) {
            $series[] = [
                'bucket' => $bucket['key'],
                'label' => $bucket['label'],
                'value' => $values[$bucket['key']] ?? 0,
            ];
        }

        return $series;
    }

    /**
     * Merge several named series into the Recharts-friendly shape:
     * [{label: '03/11', entrepreneurs: 2, investors: 1}, ...].
     *
     * @param  array<string, array<int, array{bucket: string, label: string, value: int|float}>>  $seriesByKey
     * @return array<int, array<string, string|int|float>>
     */
    public function merge(array $seriesByKey, StatisticsPeriod $period): array
    {
        $rows = [];

        foreach ($this->buckets($period) as $index => $bucket) {
            $row = ['label' => $bucket['label'], 'bucket' => $bucket['key']];

            foreach ($seriesByKey as $key => $series) {
                $row[$key] = $series[$index]['value'] ?? 0;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    public function bucketKeys(StatisticsPeriod $period): array
    {
        return array_map(fn (array $bucket) => $bucket['key'], $this->buckets($period));
    }

    public function startOfBucket(StatisticsPeriod $period): CarbonImmutable
    {
        return $period->granularity === 'month'
            ? $period->start->startOfMonth()
            : $period->start->startOfDay();
    }
}
