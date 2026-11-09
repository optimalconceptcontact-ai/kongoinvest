<?php

namespace App\Services\Statistics\Exports;

use Carbon\CarbonImmutable;

/**
 * Turns a report row into the text shown in CSV/Excel cells.
 *
 * Dates arrive as ISO-8601 strings from the service; the export formats
 * them in the analytics timezone so the file matches the screen.
 */
final class ReportCellFormatter
{
    /** @param array<string, mixed> $column */
    public function format(array $column, array $row): string
    {
        $key = (string) ($column['key'] ?? '');
        $type = (string) ($column['type'] ?? 'text');
        $value = $row[$key] ?? null;

        if ($type === 'badge') {
            return (string) ($row[$key.'_label'] ?? $this->label($value));
        }

        if ($type === 'boolean') {
            return $value ? 'Oui' : 'Non';
        }

        if ($value === null || $value === '') {
            return '—';
        }

        if ($type === 'date') {
            return $this->date((string) $value);
        }

        if ($type === 'number') {
            return number_format((float) $value, 0, ',', ' ');
        }

        if ($type === 'percent') {
            return number_format((float) $value, 1, ',', ' ').' %';
        }

        if ($type === 'currency') {
            $currencyKey = $column['currency_key'] ?? null;
            $currency = is_string($currencyKey) ? ($row[$currencyKey] ?? null) : null;

            $formatted = number_format((float) $value, 2, ',', ' ');

            return $currency !== null && $currency !== ''
                ? $formatted.' '.strtoupper((string) $currency)
                : $formatted;
        }

        if (is_bool($value)) {
            return $value ? 'Oui' : 'Non';
        }

        return (string) $value;
    }

    private function date(string $value): string
    {
        try {
            return CarbonImmutable::parse($value)
                ->setTimezone((string) config('statistics.timezone', 'Africa/Kinshasa'))
                ->format('d/m/Y H:i');
        } catch (\Throwable) {
            return $value;
        }
    }

    private function label(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'Oui' : 'Non';
        }

        return $value === null ? '—' : (string) $value;
    }
}
