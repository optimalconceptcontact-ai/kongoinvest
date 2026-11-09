import { router } from '@inertiajs/react';
import type { StatisticsFilters } from './types';

/** Only non-empty values become query parameters, so URLs stay readable. */
export function cleanQuery(
    query: StatisticsFilters,
): Record<string, string> {
    const cleaned: Record<string, string> = {};

    Object.entries(query).forEach(([key, value]) => {
        if (value === undefined || value === null || value === '') {
            return;
        }

        cleaned[key] = String(value);
    });

    return cleaned;
}

export function navigateStatistics(
    path: string,
    filters: StatisticsFilters,
    changes: StatisticsFilters = {},
): void {
    router.get(path, cleanQuery({ ...filters, ...changes }), {
        preserveState: true,
        preserveScroll: true,
    });
}

export function formatNumber(value: number): string {
    return new Intl.NumberFormat('fr-FR', {
        maximumFractionDigits: value === Math.floor(value) ? 0 : 2,
    }).format(value);
}

export function formatMoney(value: number, currency?: string | null): string {
    if (!currency) {
        return formatNumber(value);
    }

    try {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency,
            maximumFractionDigits: 2,
        }).format(value);
    } catch {
        return `${formatNumber(value)} ${currency}`;
    }
}

export function formatPercent(value: number): string {
    return `${value > 0 ? '+' : ''}${new Intl.NumberFormat('fr-FR', {
        maximumFractionDigits: 1,
    }).format(value)} %`;
}

export function formatDate(value: string | number | boolean | null): string {
    if (value === null || value === '' || typeof value !== 'string') {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('fr-FR', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: 'Africa/Kinshasa',
    }).format(date);
}

export function formatDay(value: string | number | boolean | null): string {
    if (value === null || value === '' || typeof value !== 'string') {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('fr-FR', {
        dateStyle: 'medium',
        timeZone: 'Africa/Kinshasa',
    }).format(date);
}

export function formatCellValue(
    column: { key: string; type: string; currency_key?: string },
    row: Record<string, string | number | boolean | null>,
): string {
    const value = row[column.key];

    if (column.type === 'badge') {
        const label = row[`${column.key}_label`];

        if (typeof label === 'string') {
            return label;
        }
    }

    if (column.type === 'boolean') {
        return value ? 'Oui' : 'Non';
    }

    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (column.type === 'date') {
        return formatDate(value);
    }

    if (column.type === 'number') {
        return formatNumber(Number(value));
    }

    if (column.type === 'currency') {
        const currency = column.currency_key
            ? row[column.currency_key]
            : undefined;

        return formatMoney(
            Number(value),
            typeof currency === 'string' ? currency : undefined,
        );
    }

    if (column.type === 'percent') {
        return `${formatNumber(Number(value))} %`;
    }

    if (typeof value === 'boolean') {
        return value ? 'Oui' : 'Non';
    }

    return String(value);
}
