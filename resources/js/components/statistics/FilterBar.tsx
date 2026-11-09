import { useEffect, useState } from 'react';
import { RiSearchLine } from '@remixicon/react';
import { navigateStatistics } from './utils';
import type { FilterField, StatisticsFilters } from './types';

export function FilterBar({
    path,
    filters,
    fields,
    searchPlaceholder = 'Rechercher',
}: {
    path: string;
    filters: StatisticsFilters;
    fields: FilterField[];
    searchPlaceholder?: string;
}) {
    const [search, setSearch] = useState(String(filters.search ?? ''));

    useEffect(() => {
        setSearch(String(filters.search ?? ''));
    }, [filters.search]);

    const apply = (changes: StatisticsFilters) => {
        navigateStatistics(path, filters, {
            ...changes,
            page: undefined,
        });
    };

    const reset = () => {
        const cleared: StatisticsFilters = {
            search: undefined,
            sort: undefined,
            direction: undefined,
            page: undefined,
        };

        fields.forEach((field) => {
            cleared[field.key] = undefined;
        });

        navigateStatistics(path, filters, cleared);
    };

    const submitSearch = (event: React.FormEvent) => {
        event.preventDefault();
        apply({ search: search.trim() || undefined });
    };

    const commitText = (field: FilterField, raw: string) => {
        const next = raw.trim() || undefined;
        const current = filters[field.key];

        if (String(current ?? '') === String(next ?? '')) {
            return;
        }

        apply({ [field.key]: next });
    };

    return (
        <section
            aria-label="Filtres"
            className="border border-rule-strong bg-paper p-4"
        >
            <div className="flex flex-wrap items-end gap-3">
                <form
                    onSubmit={submitSearch}
                    className="flex min-w-56 flex-1 items-end gap-2"
                >
                    <div className="min-w-0 flex-1">
                        <label
                            htmlFor="statistics-search"
                            className="text-eyebrow mb-1 block"
                        >
                            Recherche
                        </label>
                        <input
                            id="statistics-search"
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={searchPlaceholder}
                            className="min-h-11 w-full border border-rule-strong bg-paper px-3 text-base text-ink"
                        />
                    </div>
                    <button
                        type="submit"
                        className="flex min-h-11 items-center gap-2 border border-rule-strong px-3 text-btn text-ink hover:bg-surface-2"
                    >
                        <RiSearchLine size={18} />
                        <span className="hidden sm:inline">Rechercher</span>
                    </button>
                </form>

                {fields.map((field) => {
                    const id = `statistics-filter-${field.key}`;

                    if (field.type === 'text') {
                        return (
                            <div key={field.key} className="min-w-28">
                                <label
                                    htmlFor={id}
                                    className="text-eyebrow mb-1 block"
                                >
                                    {field.label}
                                </label>
                                <input
                                    key={`${field.key}-${String(filters[field.key] ?? '')}`}
                                    id={id}
                                    type="text"
                                    defaultValue={String(
                                        filters[field.key] ?? '',
                                    )}
                                    placeholder={field.placeholder}
                                    maxLength={field.max_length}
                                    onKeyDown={(event) => {
                                        if (event.key === 'Enter') {
                                            commitText(
                                                field,
                                                event.currentTarget.value,
                                            );
                                        }
                                    }}
                                    onBlur={(event) =>
                                        commitText(field, event.target.value)
                                    }
                                    className="min-h-11 w-full border border-rule-strong bg-paper px-2 text-base text-ink"
                                />
                            </div>
                        );
                    }

                    return (
                        <div key={field.key} className="min-w-36">
                            <label
                                htmlFor={id}
                                className="text-eyebrow mb-1 block"
                            >
                                {field.label}
                            </label>
                            <select
                                id={id}
                                value={String(filters[field.key] ?? '')}
                                onChange={(event) =>
                                    apply({
                                        [field.key]:
                                            event.target.value || undefined,
                                    })
                                }
                                className="min-h-11 w-full border border-rule-strong bg-paper px-2 text-base text-ink"
                            >
                                <option value="">
                                    {field.all_label ?? 'Tous'}
                                </option>
                                {(field.options ?? []).map((option) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                    );
                })}

                <button
                    type="button"
                    onClick={reset}
                    className="min-h-11 border border-rule-strong px-3 text-btn text-ink hover:bg-surface-2"
                >
                    Réinitialiser
                </button>
            </div>
        </section>
    );
}
