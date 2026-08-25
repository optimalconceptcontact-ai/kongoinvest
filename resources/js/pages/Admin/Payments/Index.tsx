import { Head, router } from '@inertiajs/react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Badge } from '@/components/Ui/Badge';

type PaymentRow = {
    id: number;
    payerName: string;
    purposeLabel: string;
    providerLabel: string;
    amountMinor: number;
    currency: string;
    status: string;
    statusLabel: string;
    failureReason: string | null;
    createdAt: string;
};
type Paginated<T> = { data: T[]; links: { url: string | null; label: string; active: boolean }[] };

const statuses = ['pending', 'processing', 'succeeded', 'failed', 'refunded', 'partially_refunded', 'cancelled'];

function formatAmount(minor: number, currency: string) {
    return `${(minor / 100).toLocaleString('fr-FR')} ${currency}`;
}

export default function Index({ payments, filters }: { payments: Paginated<PaymentRow>; filters: { status?: string } }) {
    return (
        <AdminLayout title="Paiements">
            <Head title="Admin — Paiements" />

            <p className="mb-6 border border-slate-300 bg-slate-50 px-4 py-3 text-caption">
                Vue en lecture seule sur tous les paiements (abonnements et investissements) traités par la
                plateforme.
            </p>

            <div className="mb-4 flex flex-wrap gap-2">
                <button
                    onClick={() => router.get('/admin/payments')}
                    className={`border px-3 py-1.5 text-base font-semibold ${!filters.status ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-400 text-slate-700'}`}
                >
                    Tous
                </button>
                {statuses.map((s) => (
                    <button
                        key={s}
                        onClick={() => router.get('/admin/payments', { status: s })}
                        className={`border px-3 py-1.5 text-base font-semibold capitalize ${filters.status === s ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-400 text-slate-700'}`}
                    >
                        {s}
                    </button>
                ))}
            </div>

            <div className="border border-slate-300">
                {payments.data.length === 0 && <p className="p-6 text-center text-caption">Aucun paiement.</p>}
                {payments.data.map((p) => (
                    <div key={p.id} className="flex items-center justify-between border-b border-slate-300 p-4 last:border-b-0">
                        <div>
                            <div className="text-item-title">{p.payerName}</div>
                            <div className="text-caption">
                                {p.purposeLabel} · {p.providerLabel}
                                {p.failureReason && <span className="text-orange-600"> · {p.failureReason}</span>}
                            </div>
                        </div>
                        <div className="flex items-center gap-3">
                            <span className="font-mono text-[11px] font-bold text-slate-900">{formatAmount(p.amountMinor, p.currency)}</span>
                            <Badge variant="outline">{p.statusLabel}</Badge>
                            <span className="font-mono text-[11px] text-slate-400">{new Date(p.createdAt).toLocaleDateString('fr-FR')}</span>
                        </div>
                    </div>
                ))}
            </div>

            {payments.links.length > 3 && (
                <div className="mt-6 flex flex-wrap justify-center gap-1">
                    {payments.links.map((link, i) => (
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
