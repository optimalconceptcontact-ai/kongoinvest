import { Head, router } from '@inertiajs/react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Badge } from '@/components/Ui/Badge';

type EscrowRow = {
    id: number;
    dealRoomId: number;
    projectTitle: string;
    investorName: string;
    entrepreneurName: string;
    amountMinor: number;
    currency: string;
    status: string;
    statusLabel: string;
    createdAt: string;
};
type Paginated<T> = { data: T[]; links: { url: string | null; label: string; active: boolean }[] };

const statuses = ['pending', 'funded', 'held', 'awaiting_release', 'released', 'refund_requested', 'refunded', 'cancelled', 'disputed'];

function formatAmount(minor: number, currency: string) {
    return `${(minor / 100).toLocaleString('fr-FR')} ${currency}`;
}

export default function Index({ escrows, filters }: { escrows: Paginated<EscrowRow>; filters: { status?: string } }) {
    return (
        <AdminLayout title="Escrow">
            <Head title="Admin — Escrow" />

            <p className="mb-6 border border-slate-300 bg-slate-50 px-4 py-3 text-caption">
                Vue en lecture seule sur tous les séquestres. L'historique complet (événements, litige) d'un
                séquestre se consulte dans sa Deal Room.
            </p>

            <div className="mb-4 flex flex-wrap gap-2">
                <button
                    onClick={() => router.get('/admin/escrow')}
                    className={`border px-3 py-1.5 text-base font-semibold ${!filters.status ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-400 text-slate-700'}`}
                >
                    Tous
                </button>
                {statuses.map((s) => (
                    <button
                        key={s}
                        onClick={() => router.get('/admin/escrow', { status: s })}
                        className={`border px-3 py-1.5 text-base font-semibold capitalize ${filters.status === s ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-400 text-slate-700'}`}
                    >
                        {s.replace('_', ' ')}
                    </button>
                ))}
            </div>

            <div className="border border-slate-300">
                {escrows.data.length === 0 && <p className="p-6 text-center text-caption">Aucun séquestre.</p>}
                {escrows.data.map((e) => (
                    <a
                        key={e.id}
                        href={`/deal-rooms/${e.dealRoomId}`}
                        className="flex items-center justify-between border-b border-slate-300 p-4 last:border-b-0 hover:bg-slate-50"
                    >
                        <div>
                            <div className="text-item-title">{e.projectTitle}</div>
                            <div className="text-caption">{e.investorName} → {e.entrepreneurName}</div>
                        </div>
                        <div className="flex items-center gap-3">
                            <span className="font-mono text-[11px] font-bold text-slate-900">{formatAmount(e.amountMinor, e.currency)}</span>
                            <Badge variant="outline">{e.statusLabel}</Badge>
                            <span className="font-mono text-[11px] text-slate-400">{new Date(e.createdAt).toLocaleDateString('fr-FR')}</span>
                        </div>
                    </a>
                ))}
            </div>

            {escrows.links.length > 3 && (
                <div className="mt-6 flex flex-wrap justify-center gap-1">
                    {escrows.links.map((link, i) => (
                        <button
                            key={i}
                            disabled={!link.url}
                            onClick={() => link.url && router.get(link.url)}
                            className={`min-w-[36px] border px-3 py-2 font-mono text-xs ${link.active ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-400 text-slate-700 disabled:opacity-30'}`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </AdminLayout>
    );
}
