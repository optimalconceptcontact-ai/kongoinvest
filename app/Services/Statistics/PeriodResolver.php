<?php

namespace App\Services\Statistics;

use App\Enums\StatisticsPreset;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Turns validated request input into a StatisticsPeriod.
 *
 * All periods are inclusive. The comparison period is always immediately
 * before the selected period and has exactly the same duration.
 */
final class PeriodResolver
{
    public function resolve(array $input): StatisticsPeriod
    {
        $preset = StatisticsPreset::tryFrom((string) ($input['period'] ?? StatisticsPreset::Month->value))
            ?? StatisticsPreset::Month;

        $timezone = (string) config('statistics.timezone', 'Africa/Kinshasa');
        $now = CarbonImmutable::now($timezone);

        return match ($preset) {
            StatisticsPreset::Week => $this->week($now, $timezone),
            StatisticsPreset::Month => $this->month($input, $now, $timezone),
            StatisticsPreset::Year => $this->year($input, $now, $timezone),
            StatisticsPreset::Custom => $this->custom($input, $timezone),
        };
    }

    private function week(CarbonImmutable $now, string $timezone): StatisticsPeriod
    {
        $end = $now->endOfDay();
        $start = $now->subDays(6)->startOfDay();

        $previousEnd = $start->subDay()->endOfDay();
        $previousStart = $previousEnd->subDays(6)->startOfDay();

        return new StatisticsPeriod(
            preset: StatisticsPreset::Week,
            start: $start,
            end: $end,
            previousStart: $previousStart,
            previousEnd: $previousEnd,
            granularity: 'day',
            timezone: $timezone,
            label: $this->rangeLabel($start, $end),
            previousLabel: $this->rangeLabel($previousStart, $previousEnd),
        );
    }

    private function month(array $input, CarbonImmutable $now, string $timezone): StatisticsPeriod
    {
        $year = $this->yearValue($input, $now);
        $month = $this->monthValue($input, $now);

        $start = CarbonImmutable::create($year, $month, 1, 0, 0, 0, $timezone)->startOfDay();
        $end = $start->endOfMonth()->endOfDay();

        $previousStart = $start->subMonthNoOverflow()->startOfMonth();
        $previousEnd = $previousStart->endOfMonth()->endOfDay();

        return new StatisticsPeriod(
            preset: StatisticsPreset::Month,
            start: $start,
            end: $end,
            previousStart: $previousStart,
            previousEnd: $previousEnd,
            granularity: 'day',
            timezone: $timezone,
            label: $start->locale('fr')->translatedFormat('F Y'),
            previousLabel: $previousStart->locale('fr')->translatedFormat('F Y'),
        );
    }

    private function year(array $input, CarbonImmutable $now, string $timezone): StatisticsPeriod
    {
        $year = $this->yearValue($input, $now);

        $start = CarbonImmutable::create($year, 1, 1, 0, 0, 0, $timezone)->startOfDay();
        $end = CarbonImmutable::create($year, 12, 31, 0, 0, 0, $timezone)->endOfDay();

        $previousStart = CarbonImmutable::create($year - 1, 1, 1, 0, 0, 0, $timezone)->startOfDay();
        $previousEnd = CarbonImmutable::create($year - 1, 12, 31, 0, 0, 0, $timezone)->endOfDay();

        return new StatisticsPeriod(
            preset: StatisticsPreset::Year,
            start: $start,
            end: $end,
            previousStart: $previousStart,
            previousEnd: $previousEnd,
            granularity: 'month',
            timezone: $timezone,
            label: (string) $year,
            previousLabel: (string) ($year - 1),
        );
    }

    private function custom(array $input, string $timezone): StatisticsPeriod
    {
        $from = $input['date_from'] ?? null;
        $to = $input['date_to'] ?? null;

        if (! is_string($from) || ! is_string($to)) {
            throw new InvalidArgumentException('A custom period requires date_from and date_to.');
        }

        $start = CarbonImmutable::createFromFormat('Y-m-d', $from, $timezone)?->startOfDay();
        $end = CarbonImmutable::createFromFormat('Y-m-d', $to, $timezone)?->endOfDay();

        if (! $start || ! $end || $end->lessThan($start)) {
            throw new InvalidArgumentException('Invalid custom period.');
        }

        $days = (int) $start->diffInDays($end) + 1;
        $previousEnd = $start->subDay()->endOfDay();
        $previousStart = $previousEnd->subDays($days - 1)->startOfDay();

        return new StatisticsPeriod(
            preset: StatisticsPreset::Custom,
            start: $start,
            end: $end,
            previousStart: $previousStart,
            previousEnd: $previousEnd,
            granularity: $days > 62 ? 'month' : 'day',
            timezone: $timezone,
            label: $this->rangeLabel($start, $end),
            previousLabel: $this->rangeLabel($previousStart, $previousEnd),
        );
    }

    private function yearValue(array $input, CarbonImmutable $now): int
    {
        $year = isset($input['year']) ? (int) $input['year'] : $now->year;
        $max = $now->year + 1;

        return max(2000, min($year, $max));
    }

    private function monthValue(array $input, CarbonImmutable $now): int
    {
        $month = isset($input['month']) ? (int) $input['month'] : $now->month;

        return max(1, min($month, 12));
    }

    private function rangeLabel(CarbonImmutable $start, CarbonImmutable $end): string
    {
        if ($start->isSameDay($end)) {
            return $start->format('d/m/Y');
        }

        return $start->format('d/m/Y').' – '.$end->format('d/m/Y');
    }
}
