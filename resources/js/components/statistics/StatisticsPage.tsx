import { ChartCard } from './ChartCard';
import { FilterBar } from './FilterBar';
import { KpiGrid } from './KpiGrid';
import { PeriodTabs } from './PeriodTabs';
import { StatisticsNav } from './StatisticsNav';
import { StatisticsTable } from './StatisticsTable';
import { formatDate } from './utils';
import type {
    PaginatedTable,
    StatisticsFilters as Filters,
    StatisticsOptions,
    StatisticsReport,
} from './types';

export function StatisticsPage({
    report,
    table,
    filters,
    options,
    path,
    note,
}: {
    report: StatisticsReport;
    table: PaginatedTable;
    filters: Filters;
    options: StatisticsOptions;
    path: string;
    note?: string;
}) {
    const activeFilters = Object.entries(report.filter_labels);

    return (
        <div className="space-y-6">
            <StatisticsNav />

            <PeriodTabs period={report.period} filters={filters} path={path} />

            <FilterBar
                path={path}
                filters={filters}
                fields={options.filters}
                searchPlaceholder={options.search_placeholder}
            />

            {activeFilters.length > 0 && (
                <div className="flex flex-wrap items-center gap-2 text-caption">
                    <span className="font-semibold text-ink">
                        Filtres actifs :
                    </span>
                    {activeFilters.map(([label, value]) => (
                        <span
                            key={label}
                            className="border border-rule-strong bg-surface-2 px-2 py-1"
                        >
                            {label} : {value}
                        </span>
                    ))}
                </div>
            )}

            <KpiGrid kpis={report.kpis} />

            <div className="grid gap-4 xl:grid-cols-2">
                {report.charts.map((chart) => (
                    <ChartCard key={chart.key} chart={chart} />
                ))}
            </div>

            <StatisticsTable
                columns={report.table.columns}
                table={table}
                filters={filters}
                path={path}
                title={`Détail — ${report.title}`}
            />

            <p className="text-caption">
                {note ? `${note} ` : ''}
                Données calculées à partir de la base KongoInvest le{' '}
                {formatDate(report.generated_at)} (fuseau{' '}
                {report.period.timezone}). Les montants de devises différentes
                ne sont jamais additionnés.
            </p>
        </div>
    );
}
