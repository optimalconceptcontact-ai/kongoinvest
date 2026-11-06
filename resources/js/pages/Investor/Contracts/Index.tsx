import { Head } from '@inertiajs/react';
import { Link } from '@/components/ui/Link';
import { InvestorLayout } from '@/layouts/InvestorLayout';

type ContractRow = {
    id: number;
    dealRoomId: number;
    projectTitle: string;
    otherPartyName: string;
    status: string;
    statusLabel: string;
    investmentTypeLabel: string;
    amountMinor: number;
    currency: string;
    createdAt: string;
};

const statusStyles: Record<string, string> = {
    draft: 'bg-surface-2 text-ink-muted ',
    generated: 'bg-surface-2 text-ink ',
    sent: 'bg-surface-2 text-ink ',
    viewed: 'bg-surface-2 text-ink ',
    accepted: 'bg-success/10 text-success',
    rejected: 'bg-surface-2 text-ink-muted ',
    expired: 'bg-surface-2 text-ink-muted ',
    cancelled: 'bg-surface-2 text-ink-muted ',
};

function formatAmount(minor: number, currency: string) {
    return `${(minor / 100).toLocaleString('fr-FR')} ${currency}`;
}

export default function Index({ contracts }: { contracts: ContractRow[] }) {
    return (
        <InvestorLayout title="Contrats">
            <Head title="Contrats" />

            {contracts.length === 0 ? (
                <div className="border border-rule-strong bg-paper p-6 text-body">
                    Aucun contrat pour l'instant — les contrats sont générés
                    depuis une Deal Room.
                </div>
            ) : (
                <div className="border border-rule">
                    {contracts.map((c) => (
                        <div
                            key={c.id}
                            className="flex items-center justify-between border-b border-rule p-5 last:border-b-0"
                        >
                            <div>
                                <div className="mb-1 flex items-center gap-2">
                                    <span
                                        className={`px-2 py-0.5 font-mono text-lg uppercase ${statusStyles[c.status] ?? 'bg-surface-2 text-ink-muted'}`}
                                    >
                                        {c.statusLabel}
                                    </span>
                                    <span className="font-mono text-lg text-ink-muted">
                                        {new Date(
                                            c.createdAt,
                                        ).toLocaleDateString('fr-FR')}
                                    </span>
                                </div>
                                <div className="text-item-title">
                                    {c.projectTitle}
                                </div>
                                <p className="text-caption">
                                    {c.otherPartyName} · {c.investmentTypeLabel}{' '}
                                    · {formatAmount(c.amountMinor, c.currency)}
                                </p>
                            </div>
                            <Link
                                href={`/deal-rooms/${c.dealRoomId}`}
                                variant="secondary"
                                size="sm"
                                className="py-2"
                            >
                                Ouvrir la Deal Room
                            </Link>
                        </div>
                    ))}
                </div>
            )}
        </InvestorLayout>
    );
}
