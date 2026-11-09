import { useEffect, useState } from 'react';
import { navigateStatistics } from './utils';
import type { StatisticsFilters, StatisticsPeriodInfo } from './types';

const PRESETS = [
    { value: 'week', label: 'Semaine' },
    { value: 'month', label: 'Mois' },
    { value: 'year', label: 'Année' },
    { value: 'custom', label: 'Personnalisé' },
];

const MONTHS = Array.from({ length: 12 }, (_, index) => ({
    value: index + 1,
    label: new Intl.DateTimeFormat('fr-FR', { month: 'long' }).format(
        new Date(2024, index, 1),
    ),
}));

function today(): string {
    return new Date().toISOString().slice(0, 10);
}

export function PeriodTabs({
    period,
    filters,
    path,
}: {
    period: StatisticsPeriodInfo;
    filters: StatisticsFilters;
    path: string;
}) {
    const now = new Date();
    const selectedYear = Number(filters.year) || now.getFullYear();
    const selectedMonth = Number(filters.month) || now.getMonth() + 1;
    const [dateFrom, setDateFrom] = useState(String(filters.date_from ?? ''));
    const [dateTo, setDateTo] = useState(String(filters.date_to ?? ''));

    useEffect(() => {
        setDateFrom(String(filters.date_from ?? ''));
        setDateTo(String(filters.date_to ?? ''));
    }, [filters.date_from, filters.date_to]);

    const years = Array.from({ length: 6 }, (_, index) => now.getFullYear() - index);

    const select = (preset: string) => {
        if (preset === 'week') {
            navigateStatistics(path, filters, {
                period: 'week',
                month: undefined,
                year: undefined,
                date_from: undefined,
                date_to: undefined,
                page: undefined,
            });

            return;
        }

        if (preset === 'month') {
            navigateStatistics(path, filters, {
                period: 'month',
                month: selectedMonth,
                year: selectedYear,
                date_from: undefined,
                date_to: undefined,
                page: undefined,
            });

            return;
        }

        if (preset === 'year') {
            navigateStatistics(path, filters, {
                period: 'year',
                month: undefined,
                year: selectedYear,
                date_from: undefined,
                date_to: undefined,
                page: undefined,
            });

            return;
        }

        const from = dateFrom || `${selectedYear}-${String(selectedMonth).padStart(2, '0')}-01`;
        const to = dateTo || today();

        setDateFrom(from);
        setDateTo(to);

        navigateStatistics(path, filters, {
            period: 'custom',
            month: undefined,
            year: undefined,
            date_from: from,
            date_to: to,
            page: undefined,
        });
    };

    const applyCustom = (event: React.FormEvent) => {
        event.preventDefault();

        if (!dateFrom || !dateTo) {
            return;
        }

        navigateStatistics(path, filters, {
            period: 'custom',
            date_from: dateFrom,
            date_to: dateTo,
            page: undefined,
        });
    };

    const setMonth = (month: number) => {
        navigateStatistics(path, filters, {
            period: 'month',
            month,
            year: selectedYear,
            date_from: undefined,
            date_to: undefined,
            page: undefined,
        });
    };

    const setYear = (year: number) => {
        navigateStatistics(path, filters, {
            period: period.preset === 'year' ? 'year' : 'month',
            year,
            month: period.preset === 'year' ? undefined : selectedMonth,
            date_from: undefined,
            date_to: undefined,
            page: undefined,
        });
    };

    return (
        <section
            aria-label="Période analysée"
            className="border border-rule-strong bg-paper"
        >
            <div className="flex flex-wrap border-b border-rule">
                {PRESETS.map((preset) => (
                    <button
                        key={preset.value}
                        type="button"
                        onClick={() => select(preset.value)}
                        aria-pressed={period.preset === preset.value}
                        className={`min-h-11 border-r border-rule px-4 text-btn last:border-r-0 ${
                            period.preset === preset.value
                                ? 'bg-ink text-paper'
                                : 'text-ink hover:bg-surface-2'
                        }`}
                    >
                        {preset.label}
                    </button>
                ))}
            </div>

            <div className="flex flex-wrap items-end gap-3 p-4">
                <div className="min-w-40">
                    <label
                        htmlFor="statistics-month"
                        className="text-eyebrow mb-1 block"
                    >
                        Mois
                    </label>
                    <select
                        id="statistics-month"
                        value={selectedMonth}
                        onChange={(event) =>
                            setMonth(Number(event.target.value))
                        }
                        disabled={period.preset === 'week' || period.preset === 'year' || period.preset === 'custom'}
                        className="min-h-11 w-full border border-rule-strong bg-paper px-2 text-base text-ink disabled:opacity-40"
                    >
                        {MONTHS.map((month) => (
                            <option key={month.value} value={month.value}>
                                {month.label}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="min-w-32">
                    <label
                        htmlFor="statistics-year"
                        className="text-eyebrow mb-1 block"
                    >
                        Année
                    </label>
                    <select
                        id="statistics-year"
                        value={selectedYear}
                        onChange={(event) =>
                            setYear(Number(event.target.value))
                        }
                        disabled={period.preset === 'week' || period.preset === 'custom'}
                        className="min-h-11 w-full border border-rule-strong bg-paper px-2 text-base text-ink disabled:opacity-40"
                    >
                        {years.map((year) => (
                            <option key={year} value={year}>
                                {year}
                            </option>
                        ))}
                    </select>
                </div>

                {period.preset === 'custom' && (
                    <form
                        onSubmit={applyCustom}
                        className="flex flex-wrap items-end gap-3"
                    >
                        <div>
                            <label
                                htmlFor="statistics-date-from"
                                className="text-eyebrow mb-1 block"
                            >
                                Du
                            </label>
                            <input
                                id="statistics-date-from"
                                type="date"
                                value={dateFrom}
                                max={dateTo || undefined}
                                onChange={(event) =>
                                    setDateFrom(event.target.value)
                                }
                                className="min-h-11 border border-rule-strong bg-paper px-2 text-base text-ink"
                            />
                        </div>
                        <div>
                            <label
                                htmlFor="statistics-date-to"
                                className="text-eyebrow mb-1 block"
                            >
                                Au
                            </label>
                            <input
                                id="statistics-date-to"
                                type="date"
                                value={dateTo}
                                min={dateFrom || undefined}
                                onChange={(event) =>
                                    setDateTo(event.target.value)
                                }
                                className="min-h-11 border border-rule-strong bg-paper px-2 text-base text-ink"
                            />
                        </div>
                        <button
                            type="submit"
                            className="min-h-11 border border-ink bg-ink px-4 text-btn text-paper"
                        >
                            Appliquer
                        </button>
                    </form>
                )}

                <p className="ml-auto text-caption">
                    {period.label} · comparé à {period.previous_label}
                </p>
            </div>
        </section>
    );
}
