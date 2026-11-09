import { RiDownload2Line, RiPrinterLine } from '@remixicon/react';
import { cleanQuery } from './utils';
import type { StatisticsFilters } from './types';

const FORMATS = [
    { format: 'pdf', label: 'PDF' },
    { format: 'xlsx', label: 'Excel' },
    { format: 'csv', label: 'CSV' },
];

export function ExportActions({
    path,
    filters,
    canExport,
}: {
    path: string;
    filters: StatisticsFilters;
    canExport: boolean;
}) {
    const query = new URLSearchParams(
        cleanQuery({ ...filters, page: undefined }),
    ).toString();
    const suffix = query ? `?${query}` : '';

    return (
        <div className="flex flex-wrap items-center gap-2 print:hidden">
            <button
                type="button"
                onClick={() => window.print()}
                className="flex min-h-11 items-center gap-2 border border-rule-strong bg-paper px-3 text-btn text-ink hover:bg-surface-2"
            >
                <RiPrinterLine size={18} />
                Imprimer
            </button>

            {canExport ? (
                FORMATS.map((item) => (
                    <a
                        key={item.format}
                        href={`${path}/${item.format}${suffix}`}
                        className="flex min-h-11 items-center gap-2 border border-rule-strong bg-paper px-3 text-btn text-ink hover:bg-surface-2"
                    >
                        <RiDownload2Line size={18} />
                        {item.label}
                    </a>
                ))
            ) : (
                <span className="text-caption">
                    Export réservé aux administrateurs autorisés.
                </span>
            )}
        </div>
    );
}
