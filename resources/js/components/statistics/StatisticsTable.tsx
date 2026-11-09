import { router } from '@inertiajs/react';
import { RiArrowDownLine, RiArrowUpLine } from '@remixicon/react';
import { formatCellValue, navigateStatistics } from './utils';
import type {
    PaginatedTable,
    StatisticsFilters,
    TableColumn,
    TableRow,
} from './types';

function SortButton({
    column,
    filters,
    path,
}: {
    column: TableColumn;
    filters: StatisticsFilters;
    path: string;
}) {
    const active = String(filters.sort ?? 'created_at') === column.key;
    const direction = String(filters.direction ?? 'desc');
    const nextDirection = active && direction === 'desc' ? 'asc' : 'desc';

    return (
        <button
            type="button"
            onClick={() =>
                navigateStatistics(path, filters, {
                    sort: column.key,
                    direction: nextDirection,
                    page: undefined,
                })
            }
            className="flex items-center gap-1 text-left font-semibold text-ink hover:text-action"
        >
            {column.label}
            {active ? (
                direction === 'desc' ? (
                    <RiArrowDownLine size={14} aria-hidden />
                ) : (
                    <RiArrowUpLine size={14} aria-hidden />
                )
            ) : null}
        </button>
    );
}

function Cell({ column, row }: { column: TableColumn; row: TableRow }) {
    const value = formatCellValue(column, row);

    if (column.type === 'badge') {
        const isActive = row[column.key] === 'active';

        return (
            <span
                className={`inline-flex border px-2 py-0.5 text-sm ${
                    isActive
                        ? 'border-success text-success'
                        : 'border-rule-strong text-ink-muted'
                }`}
            >
                {value}
            </span>
        );
    }

    return <span>{value}</span>;
}

export function StatisticsTable({
    columns,
    table,
    filters,
    path,
    title = 'Détail des données',
}: {
    columns: TableColumn[];
    table: PaginatedTable;
    filters: StatisticsFilters;
    path: string;
    title?: string;
}) {
    const goToPage = (url: string | null) => {
        if (url) {
            router.get(url, {}, { preserveState: true, preserveScroll: true });
        }
    };

    return (
        <section className="border border-rule-strong bg-paper">
            <div className="flex flex-wrap items-end justify-between gap-2 border-b border-rule p-4">
                <div>
                    <h2 className="text-item-title">{title}</h2>
                    <p className="text-caption">
                        {table.total === 0
                            ? 'Aucun utilisateur ne correspond aux filtres.'
                            : `${table.from ?? 0}–${table.to ?? 0} sur ${table.total}`}
                    </p>
                </div>
            </div>

            <div className="overflow-x-auto">
                <table className="w-full min-w-[900px] border-collapse text-base">
                    <thead>
                        <tr className="border-b border-rule-strong bg-surface-2">
                            {columns.map((column) => (
                                <th
                                    key={column.key}
                                    scope="col"
                                    className="px-3 py-2 text-left text-sm text-ink-muted"
                                >
                                    {column.sortable ? (
                                        <SortButton
                                            column={column}
                                            filters={filters}
                                            path={path}
                                        />
                                    ) : (
                                        <span className="font-semibold">
                                            {column.label}
                                        </span>
                                    )}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {table.data.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={columns.length}
                                    className="px-3 py-8 text-center text-caption"
                                >
                                    Aucune donnée sur la période sélectionnée.
                                </td>
                            </tr>
                        ) : (
                            table.data.map((row) => (
                                <tr
                                    key={String(row.id ?? `${row.name}-${row.email}`)}
                                    className="border-b border-rule last:border-b-0"
                                >
                                    {columns.map((column) => (
                                        <td
                                            key={column.key}
                                            className="px-3 py-2 align-top text-ink"
                                        >
                                            <Cell column={column} row={row} />
                                        </td>
                                    ))}
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            {table.last_page > 1 && (
                <div className="flex flex-wrap items-center justify-between gap-2 border-t border-rule p-3">
                    <span className="text-caption">
                        Page {table.current_page} sur {table.last_page}
                    </span>
                    <div className="flex flex-wrap gap-1">
                        {table.links.map((link, index) => (
                            <button
                                key={`${link.label}-${index}`}
                                type="button"
                                disabled={!link.url}
                                onClick={() => goToPage(link.url)}
                                className={`min-h-11 min-w-11 border px-3 py-2 font-mono text-base ${
                                    link.active
                                        ? 'border-ink bg-ink text-paper'
                                        : 'border-rule-strong text-ink disabled:opacity-30'
                                }`}
                                dangerouslySetInnerHTML={{
                                    __html: link.label,
                                }}
                            />
                        ))}
                    </div>
                </div>
            )}
        </section>
    );
}
