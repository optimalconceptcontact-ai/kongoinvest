import { Head } from '@inertiajs/react';
import { InvestorLayout } from '@/layouts/InvestorLayout';
import { Link } from '@/components/Ui/Link';

type Investment = {
    id: number;
    dealRoomId: number;
    projectTitle: string;
    entrepreneurName: string;
    amountMinor: number;
    currency: string;
    status: string;
    statusLabel: string;
    investmentTypeLabel: string;
    fundedAt: string | null;
    releasedAt: string | null;
};

type Summary = {
    totalInvestments: number;
    activeCount: number;
    releasedCount: number;
    totalsByCurrency: Record<string, number>;
};

const statusStyles: Record<string, string> = {
    pending: 'bg-slate-100 text-slate-400',
    funded: 'bg-blue-50 text-blue-700',
    held: 'bg-blue-50 text-blue-700',
    awaiting_release: 'bg-orange-50 text-orange-700',
    released: 'bg-emerald-50 text-emerald-700',
    refund_requested: 'bg-orange-50 text-orange-700',
    refunded: 'bg-slate-100 text-slate-400',
    cancelled: 'bg-slate-100 text-slate-400',
    disputed: 'bg-orange-50 text-orange-700',
};

function formatAmount(minor: number, currency: string) {
    return `${(minor / 100).toLocaleString('fr-FR')} ${currency}`;
}

export default function Index({ summary, investments }: { summary: Summary; investments: Investment[] }) {
    const currencies = Object.keys(summary.totalsByCurrency);

    return (
        <InvestorLayout title="Portefeuille">
            <Head title="Portefeuille" />

            <div className="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div className="border border-slate-200 p-4">
                    <div className="text-eyebrow">Investissements</div>
                    <div className="mt-1 font-mono text-2xl font-bold text-slate-900">{summary.totalInvestments}</div>
                </div>
                <div className="border border-slate-200 p-4">
                    <div className="text-eyebrow">Actifs</div>
                    <div className="mt-1 font-mono text-2xl font-bold text-slate-900">{summary.activeCount}</div>
                </div>
                <div className="border border-slate-200 p-4">
                    <div className="text-eyebrow">Libérés</div>
                    <div className="mt-1 font-mono text-2xl font-bold text-slate-900">{summary.releasedCount}</div>
                </div>
                <div className="border border-slate-200 p-4">
                    <div className="text-eyebrow">Total engagé</div>
                    {currencies.length === 0 ? (
                        <div className="mt-1 font-mono text-2xl font-bold text-slate-900">—</div>
                    ) : (
                        currencies.map((c) => (
                            <div key={c} className="mt-1 font-mono text-lg font-bold text-slate-900">
                                {formatAmount(summary.totalsByCurrency[c], c)}
                            </div>
                        ))
                    )}
                </div>
            </div>

            {investments.length === 0 ? (
                <div className="border border-dashed border-slate-400 py-16 text-center text-body">
                    Aucun investissement pour l'instant — un séquestre apparaît ici dès qu'un contrat est financé.
                </div>
            ) : (
                <div className="border border-slate-200">
                    {investments.map((i) => (
                        <div key={i.id} className="flex items-center justify-between border-b border-slate-200 p-5 last:border-b-0">
                            <div>
                                <div className="mb-1 flex items-center gap-2">
                                    <span className={`px-2 py-0.5 font-mono text-[11px] uppercase ${statusStyles[i.status] ?? 'bg-slate-100 text-slate-400'}`}>
                                        {i.statusLabel}
                                    </span>
                                    {i.fundedAt && (
                                        <span className="font-mono text-[11px] text-slate-400">
                                            financé le {new Date(i.fundedAt).toLocaleDateString('fr-FR')}
                                        </span>
                                    )}
                                </div>
                                <div className="text-item-title">{i.projectTitle}</div>
                                <p className="text-caption">
                                    {i.entrepreneurName} · {i.investmentTypeLabel}
                                </p>
                            </div>
                            <div className="flex items-center gap-4">
                                <span className="font-mono text-sm font-bold text-slate-900">{formatAmount(i.amountMinor, i.currency)}</span>
                                <Link href={`/deal-rooms/${i.dealRoomId}`} variant="secondary" size="sm" className="py-2">
                                    Ouvrir la Deal Room
                                </Link>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </InvestorLayout>
    );
}
