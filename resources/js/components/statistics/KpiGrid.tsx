import { RiArrowDownLine, RiArrowUpLine } from '@remixicon/react';
import { formatMoney, formatNumber, formatPercent } from './utils';
import type { StatisticsKpi } from './types';

function kpiValue(kpi: StatisticsKpi): string {
    if (kpi.format === 'currency') {
        return formatMoney(kpi.value, kpi.currency);
    }

    if (kpi.format === 'percent') {
        return `${formatNumber(kpi.value)} %`;
    }

    return formatNumber(kpi.value);
}

export function KpiGrid({ kpis }: { kpis: StatisticsKpi[] }) {
    if (kpis.length === 0) {
        return null;
    }

    return (
        <section
            aria-label="Indicateurs clés"
            className="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-4"
        >
            {kpis.map((kpi) => {
                const variation = kpi.variation;

                return (
                    <article
                        key={kpi.key}
                        className="border border-rule-strong bg-paper p-4"
                    >
                        <div className="text-caption">{kpi.label}</div>
                        <div className="mt-1 text-stat">
                            {kpiValue(kpi)}
                        </div>
                        {variation !== null ? (
                            <div className="mt-1 flex items-center gap-1 text-caption">
                                {variation >= 0 ? (
                                    <RiArrowUpLine size={14} />
                                ) : (
                                    <RiArrowDownLine size={14} />
                                )}
                                <span>{formatPercent(variation)}</span>
                                {kpi.variation_label ? (
                                    <span>vs {kpi.variation_label}</span>
                                ) : null}
                            </div>
                        ) : kpi.hint ? (
                            <div className="mt-1 text-caption">{kpi.hint}</div>
                        ) : null}
                    </article>
                );
            })}
        </section>
    );
}
