import { Head, router } from '@inertiajs/react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Badge } from '@/components/ui/badge';

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
type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

const statuses = [
    'pending',
    'processing',
    'succeeded',
    'failed',
    'refunded',
    'partially_refunded',
    'cancelled',
];

function formatAmount(minor: number, currency: string) {
    return `${(minor / 100).toLocaleString('fr-FR')} ${currency}`;
}

export default function Index({
    payments,
    filters,
}: {
    payments: Paginated<PaymentRow>;
    filters: { status?: string };
}) {
    return (
        <AdminLayout title="Paiements">
            <Head title="Admin — Paiements" />

            <p className="mb-6 border border-rule bg-surface-2 px-4 py-3 text-caption">
                Vue en lecture seule sur tous les paiements (abonnements et
                investissements) traités par la plateforme.
            </p>

            <div className="mb-4 flex flex-wrap gap-2">
                <button
                    onClick={() => router.get('/admin/payments')}
                    className={`min-h-11 border px-3 py-1.5 text-btn ${!filters.status ? 'border-ink bg-ink text-paper' : 'border-rule-strong text-ink'}`}
                >
                    Tous
                </button>
                {statuses.map((s) => (
                    <button
                        key={s}
                        onClick={() =>
                            router.get('/admin/payments', { status: s })
                        }
                        className={`min-h-11 border px-3 py-1.5 text-btn capitalize ${filters.status === s ? 'border-ink bg-ink text-paper' : 'border-rule-strong text-ink'}`}
                    >
                        {s}
                    </button>
                ))}
            </div>

            <div className="border border-rule">
                {payments.data.length === 0 && (
                    <p className="p-6 text-center text-caption">
                        Aucun paiement.
                    </p>
                )}
                {payments.data.map((p) => (
                    <div
                        key={p.id}
                        className="flex items-center justify-between border-b border-rule p-4 last:border-b-0"
                    >
                        <div>
                            <div className="text-item-title">{p.payerName}</div>
                            <div className="text-caption">
                                {p.purposeLabel} · {p.providerLabel}
                                {p.failureReason && (
                                    <span className="text-danger">
                                        {' '}
                                        · {p.failureReason}
                                    </span>
                                )}
                            </div>
                        </div>
                        <div className="flex items-center gap-3">
                            <span className="font-mono text-lg font-bold text-ink">
                                {formatAmount(p.amountMinor, p.currency)}
                            </span>
                            <Badge variant="outline">{p.statusLabel}</Badge>
                            <span className="font-mono text-lg text-ink-muted">
                                {new Date(p.createdAt).toLocaleDateString(
                                    'fr-FR',
                                )}
                            </span>
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
                            className={`min-h-11 min-w-11 border px-3 py-2 font-mono text-lg ${link.active ? 'border-ink bg-ink text-paper' : 'border-rule-strong text-ink disabled:opacity-30'}`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </AdminLayout>
    );
}
