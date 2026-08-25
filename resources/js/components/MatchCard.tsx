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

function scoreColor(score: number) {
    if (score >= 75) return 'text-blue-600 border-blue-200 bg-blue-50';
    if (score >= 50) return 'text-orange-600 border-orange-200 bg-orange-50';
    return 'text-slate-500 border-slate-200 bg-slate-50';
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
    return (
        <div className="border border-slate-200 bg-white p-5">
            <div className="mb-4 flex items-start justify-between gap-4">
                <div>
                    {href ? (
                        <Link href={href} className="text-item-title hover:text-blue-700">
                            {title}
                        </Link>
                    ) : (
                        <div className="text-item-title">{title}</div>
                    )}
                    <div className="text-caption">{subtitle}</div>
                </div>
                <div className={`flex h-14 w-14 flex-shrink-0 items-center justify-center border font-mono text-lg font-bold ${scoreColor(match.score)}`}>
                    {match.score}
                </div>
            </div>

            <div className="flex flex-col gap-2">
                {match.breakdown.map((item) => (
                    <div key={item.key} className="flex items-center gap-3">
                        <div className="w-28 flex-shrink-0 text-xs text-slate-700">{item.label}</div>
                        <div className="h-1.5 flex-1 bg-slate-100">
                            <div
                                className={`h-full ${item.source === 'ai' ? 'bg-orange-500' : item.source === 'fallback' ? 'bg-slate-300' : 'bg-blue-500'}`}
                                style={{ width: `${item.score}%` }}
                            />
                        </div>
                        <div className="w-16 flex-shrink-0 text-right font-mono text-[11px] text-slate-400">
                            {item.score} · {sourceLabels[item.source]}
                        </div>
                    </div>
                ))}
            </div>

            {actions && <div className="mt-4 flex gap-2 border-t border-slate-100 pt-4">{actions}</div>}
        </div>
    );
}
