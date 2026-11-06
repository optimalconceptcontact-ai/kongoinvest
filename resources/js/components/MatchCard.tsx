import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

export type MatchBreakdownItem = {
    key: string;
    label: string;
    score: number;
    weight: number;
    weightedContribution: number;
    source: 'rule' | 'ai' | 'fallback';
};

export type MatchCardData = {
    id: number;
    score: number;
    status: string;
    computedAt: string;
    breakdown: MatchBreakdownItem[];
    projectTitle: string;
    projectSlug: string;
    investorName: string;
};

const sourceLabels: Record<string, string> = {
    rule: 'Règle',
    ai: 'IA',
    fallback: 'Estimation',
};

function tierForScore(score: number) {
    if (score >= 75) {
        return 'Forte';
    }

    if (score >= 50) {
        return 'Moyenne';
    }

    return 'Faible';
}

export function MatchCard({
    match,
    title,
    subtitle,
    href,
    actions,
}: {
    match: MatchCardData;
    title: string;
    subtitle: string;
    href?: string;
    actions?: ReactNode;
}) {
    const criteria = [...match.breakdown].sort(
        (a, b) => b.weightedContribution - a.weightedContribution,
    );
    const topCriteria = criteria.slice(0, 3);
    const otherCount = criteria.length - topCriteria.length;
    const hasOutOfRange = criteria.some((item) => item.score < 50);

    return (
        <article className="flex flex-col gap-3.5 border border-rule-strong bg-paper p-[18px]">
            <div className="flex items-start justify-between gap-4">
                <div className="min-w-0">
                    <h3 className="text-item-title">{title}</h3>
                    <p className="mt-1 text-lg leading-5 text-ink-muted">
                        {subtitle}
                    </p>
                </div>
                <div className="flex-shrink-0 text-right">
                    <div className="font-mono text-7xl font-bold tracking-[-0.03em] text-ink tabular-nums">
                        {match.score}
                    </div>
                    <div className="my-1.5 ml-auto h-[3px] w-7 bg-signal" />
                    <span className="block font-mono text-lg font-medium tracking-[0.06em] text-ink-muted uppercase">
                        {tierForScore(match.score)}
                    </span>
                </div>
            </div>

            <table className="w-full border-collapse">
                <caption className="sr-only">
                    Principaux critères de correspondance
                </caption>
                <thead>
                    <tr>
                        <th
                            scope="col"
                            className="border-b border-rule-strong pb-1.5 text-left font-mono text-lg font-medium tracking-[0.08em] text-ink-muted uppercase"
                        >
                            Critère
                        </th>
                        <th
                            scope="col"
                            className="border-b border-rule-strong pb-1.5 text-right font-mono text-lg font-medium tracking-[0.08em] text-ink-muted uppercase"
                        >
                            Note
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {topCriteria.map((item) => (
                        <tr
                            key={item.key}
                            className="border-b border-rule last:border-b-0"
                        >
                            <th
                                scope="row"
                                className="py-2 text-left align-top font-normal"
                            >
                                <span className="block font-mono text-[16px] leading-5 text-ink">
                                    {item.label}
                                </span>
                                <span className="mt-0.5 block text-lg leading-4 text-ink-muted">
                                    {sourceLabels[item.source]}
                                </span>
                            </th>
                            <td className="py-2 text-right align-top font-mono text-[16px] leading-5 text-ink tabular-nums">
                                {item.score}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>

            <div className="mt-auto flex flex-wrap items-center justify-between gap-3 border-t border-rule pt-3">
                {hasOutOfRange ? (
                    <span className="inline-flex items-center gap-1.5 font-mono text-lg leading-5 text-danger">
                        <span className="h-[7px] w-[7px] flex-shrink-0 bg-danger" />
                        1 critère hors fourchette
                    </span>
                ) : otherCount > 0 ? (
                    <span className="font-mono text-lg leading-5 text-ink-muted">
                        + {otherCount} autres critères
                    </span>
                ) : (
                    <span className="font-mono text-lg leading-5 text-ink-muted">
                        Score sur 100
                    </span>
                )}
                {href && (
                    <Link
                        href={href}
                        className="inline-flex min-h-11 items-center font-mono text-lg font-medium text-action underline decoration-1 underline-offset-4 hover:decoration-2"
                    >
                        Voir la fiche
                    </Link>
                )}
            </div>

            {actions && (
                <div className="flex flex-wrap gap-2 border-t border-rule pt-3">
                    {actions}
                </div>
            )}
        </article>
    );
}
