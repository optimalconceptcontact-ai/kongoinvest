import { Head, router } from '@inertiajs/react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Badge } from '@/components/ui/badge';

type ContractRow = {
    id: number;
    dealRoomId: number;
    projectTitle: string;
    investorName: string;
    entrepreneurName: string;
    status: string;
    statusLabel: string;
    investmentTypeLabel: string;
    amountMinor: number;
    currency: string;
    createdAt: string;
};
type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

const statuses = [
    'draft',
    'generated',
    'sent',
    'viewed',
    'accepted',
    'rejected',
    'expired',
    'cancelled',
];

function formatAmount(minor: number, currency: string) {
    return `${(minor / 100).toLocaleString('fr-FR')} ${currency}`;
}

export default function Index({
    contracts,
    filters,
}: {
    contracts: Paginated<ContractRow>;
    filters: { status?: string };
}) {
    return (
        <AdminLayout title="Contrats">
            <Head title="Admin — Contrats" />

            <p className="mb-6 border border-rule bg-surface-2 px-4 py-3 text-caption">
                Vue en lecture seule sur tous les contrats. Les versions et la
                négociation d'un contrat se consultent dans sa Deal Room.
            </p>

            <div className="mb-4 flex flex-wrap gap-2">
                <button
                    onClick={() => router.get('/admin/contracts')}
                    className={`min-h-11 border px-3 py-1.5 text-btn ${!filters.status ? 'border-ink bg-ink text-paper' : 'border-rule-strong text-ink'}`}
                >
                    Tous
                </button>
                {statuses.map((s) => (
                    <button
                        key={s}
                        onClick={() =>
                            router.get('/admin/contracts', { status: s })
                        }
                        className={`min-h-11 border px-3 py-1.5 text-btn capitalize ${filters.status === s ? 'border-ink bg-ink text-paper' : 'border-rule-strong text-ink'}`}
                    >
                        {s}
                    </button>
                ))}
            </div>

            <div className="border border-rule">
                {contracts.data.length === 0 && (
                    <p className="p-6 text-center text-caption">
                        Aucun contrat.
                    </p>
                )}
                {contracts.data.map((c) => (
                    <a
                        key={c.id}
                        href={`/deal-rooms/${c.dealRoomId}`}
                        className="flex items-center justify-between border-b border-rule p-4 last:border-b-0 hover:bg-surface-2"
                    >
                        <div>
                            <div className="text-item-title">
                                {c.projectTitle}
                            </div>
                            <div className="text-caption">
                                {c.investorName} → {c.entrepreneurName} ·{' '}
                                {c.investmentTypeLabel}
                            </div>
                        </div>
                        <div className="flex items-center gap-3">
                            <span className="font-mono text-lg font-bold text-ink">
                                {formatAmount(c.amountMinor, c.currency)}
                            </span>
                            <Badge variant="outline">{c.statusLabel}</Badge>
                            <span className="font-mono text-lg text-ink-muted">
                                {new Date(c.createdAt).toLocaleDateString(
                                    'fr-FR',
                                )}
                            </span>
                        </div>
                    </a>
                ))}
            </div>

            {contracts.links.length > 3 && (
                <div className="mt-6 flex flex-wrap justify-center gap-1">
                    {contracts.links.map((link, i) => (
                        <button
                            key={i}
                            disabled={!link.url}
                            onClick={() => link.url && router.get(link.url)}
                            className={`min-h-11 min-w-11 border px-3 py-2 font-mono text-lg ${link.active ? 'border-ink bg-ink text-paper' : 'border-rule-strong text-ink disabled:opacity-30'}`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </AdminLayout>
    );
}
