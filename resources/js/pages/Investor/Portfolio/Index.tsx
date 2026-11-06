import { Head } from '@inertiajs/react';
import { Link } from '@/components/ui/Link';
import { InvestorLayout } from '@/layouts/InvestorLayout';

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
    pending: 'bg-surface-2 text-ink-muted ',
    funded: 'bg-surface-2 text-ink ',
    held: 'bg-surface-2 text-ink ',
    awaiting_release: 'bg-surface-2 text-ink ',
    released: 'bg-success/10 text-success',
    refund_requested: 'bg-surface-2 text-ink ',
    refunded: 'bg-surface-2 text-ink-muted ',
    cancelled: 'bg-surface-2 text-ink-muted ',
    disputed: 'bg-surface-2 text-ink ',
};

function formatAmount(minor: number, currency: string) {
    return `${(minor / 100).toLocaleString('fr-FR')} ${currency}`;
}

export default function Index({
    summary,
    investments,
}: {
    summary: Summary;
    investments: Investment[];
}) {
    const currencies = Object.keys(summary.totalsByCurrency);

    return (
        <InvestorLayout title="Portefeuille">
            <Head title="Portefeuille" />

            <div className="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div className="border border-rule p-4">
                    <div className="text-eyebrow">Investissements</div>
                    <div className="mt-1 font-mono text-4xl font-bold text-ink">
                        {summary.totalInvestments}
                    </div>
                </div>
                <div className="border border-rule p-4">
                    <div className="text-eyebrow">Actifs</div>
                    <div className="mt-1 font-mono text-4xl font-bold text-ink">
                        {summary.activeCount}
                    </div>
                </div>
                <div className="border border-rule p-4">
                    <div className="text-eyebrow">Libérés</div>
                    <div className="mt-1 font-mono text-4xl font-bold text-ink">
                        {summary.releasedCount}
                    </div>
                </div>
                <div className="border border-rule p-4">
                    <div className="text-eyebrow">Total engagé</div>
                    {currencies.length === 0 ? (
                        <div className="mt-1 font-mono text-4xl font-bold text-ink">
                            —
                        </div>
                    ) : (
                        currencies.map((c) => (
                            <div
                                key={c}
                                className="mt-1 font-mono text-2xl font-bold text-ink"
                            >
                                {formatAmount(summary.totalsByCurrency[c], c)}
                            </div>
                        ))
                    )}
                </div>
            </div>

            {investments.length === 0 ? (
                <div className="border border-rule-strong bg-paper p-6 text-body">
                    Aucun investissement pour l'instant — un séquestre apparaît
                    ici dès qu'un contrat est financé.
                </div>
            ) : (
                <div className="border border-rule">
                    {investments.map((i) => (
                        <div
                            key={i.id}
                            className="flex items-center justify-between border-b border-rule p-5 last:border-b-0"
                        >
                            <div>
                                <div className="mb-1 flex items-center gap-2">
                                    <span
                                        className={`px-2 py-0.5 font-mono text-lg uppercase ${statusStyles[i.status] ?? 'bg-surface-2 text-ink-muted'}`}
                                    >
                                        {i.statusLabel}
                                    </span>
                                    {i.fundedAt && (
                                        <span className="font-mono text-lg text-ink-muted">
                                            financé le{' '}
                                            {new Date(
                                                i.fundedAt,
                                            ).toLocaleDateString('fr-FR')}
                                        </span>
                                    )}
                                </div>
                                <div className="text-item-title">
                                    {i.projectTitle}
                                </div>
                                <p className="text-caption">
                                    {i.entrepreneurName} ·{' '}
                                    {i.investmentTypeLabel}
                                </p>
                            </div>
                            <div className="flex items-center gap-4">
                                <span className="font-mono text-lg font-bold text-ink">
                                    {formatAmount(i.amountMinor, i.currency)}
                                </span>
                                <Link
                                    href={`/deal-rooms/${i.dealRoomId}`}
                                    variant="secondary"
                                    size="sm"
                                    className="py-2"
                                >
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
