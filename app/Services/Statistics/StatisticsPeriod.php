<?php

namespace App\Services\Statistics;

use App\Enums\StatisticsPreset;
use Carbon\CarbonImmutable;

/**
 * Immutable description of the analysed period and its comparison period.
 *
 * Boundaries are inclusive and expressed in the analytics timezone for
 * display; `startUtc()` / `endUtc()` are the values used in SQL because the
 * application stores timestamps in UTC.
 */
final class StatisticsPeriod
{
    public function __construct(
        public readonly StatisticsPreset $preset,
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
        public readonly CarbonImmutable $previousStart,
        public readonly CarbonImmutable $previousEnd,
        public readonly string $granularity,
        public readonly string $timezone,
        public readonly string $label,
        public readonly string $previousLabel,
    ) {}

    public function startUtc(): CarbonImmutable
    {
        return $this->start->setTimezone('UTC');
    }

    public function endUtc(): CarbonImmutable
    {
        return $this->end->setTimezone('UTC');
    }

    public function previousStartUtc(): CarbonImmutable
    {
        return $this->previousStart->setTimezone('UTC');
    }

    public function previousEndUtc(): CarbonImmutable
    {
        return $this->previousEnd->setTimezone('UTC');
    }

    /** Number of inclusive days in the selected period. */
    public function days(): int
    {
        return (int) $this->start->diffInDays($this->end) + 1;
    }

    public function bucketFormat(): string
    {
        return $this->granularity === 'month' ? 'Y-m' : 'Y-m-d';
    }

    public function bucketLabel(string $bucket): string
    {
        $date = $this->granularity === 'month'
            ? CarbonImmutable::createFromFormat('Y-m', $bucket, $this->timezone)
            : CarbonImmutable::createFromFormat('Y-m-d', $bucket, $this->timezone);

        if (! $date) {
            return $bucket;
        }

        return $this->granularity === 'month'
            ? $date->format('m/Y')
            : $date->format('d/m');
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'preset' => $this->preset->value,
            'preset_label' => $this->preset->label(),
            'granularity' => $this->granularity,
            'timezone' => $this->timezone,
            'start' => $this->start->toIso8601String(),
            'end' => $this->end->toIso8601String(),
            'previous_start' => $this->previousStart->toIso8601String(),
            'previous_end' => $this->previousEnd->toIso8601String(),
            'label' => $this->label,
            'previous_label' => $this->previousLabel,
            'days' => $this->days(),
        ];
    }
}
