import { Head, router } from '@inertiajs/react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Badge } from '@/components/Ui/Badge';

type SubscriptionRow = {
    id: number;
    userName: string;
    userEmail: string;
    planName: string;
    priceMinor: number;
    currency: string;
    status: string;
    statusLabel: string;
    startsAt: string;
    endsAt: string | null;
};
type Paginated<T> = { data: T[]; links: { url: string | null; label: string; active: boolean }[] };

const statuses = ['active', 'trialing', 'past_due', 'canceled', 'expired'];

function formatPrice(minor: number, currency: string) {
    if (minor === 0) return 'Gratuit';
    return `${(minor / 100).toLocaleString('fr-FR')} ${currency}/mois`;
}

export default function Index({ subscriptions, filters }: { subscriptions: Paginated<SubscriptionRow>; filters: { status?: string } }) {
    return (
        <AdminLayout title="Abonnements">
            <Head title="Admin — Abonnements" />

            <p className="mb-6 border border-slate-300 bg-slate-50 px-4 py-3 text-caption">
                Vue en lecture seule sur les abonnements de tous les utilisateurs. Le catalogue de plans se
                configure ailleurs (spec §98).
            </p>

            <div className="mb-4 flex gap-2">
                <button
                    onClick={() => router.get('/admin/subscriptions')}
                    className={`border px-3 py-1.5 text-base font-semibold ${!filters.status ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-400 text-slate-700'}`}
                >
                    Tous
                </button>
                {statuses.map((s) => (
                    <button
                        key={s}
                        onClick={() => router.get('/admin/subscriptions', { status: s })}
                        className={`border px-3 py-1.5 text-base font-semibold capitalize ${filters.status === s ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-400 text-slate-700'}`}
                    >
                        {s}
                    </button>
                ))}
            </div>

            <div className="border border-slate-300">
                {subscriptions.data.length === 0 && <p className="p-6 text-center text-caption">Aucun abonnement.</p>}
                {subscriptions.data.map((s) => (
                    <div key={s.id} className="flex items-center justify-between border-b border-slate-300 p-4 last:border-b-0">
                        <div>
                            <div className="text-item-title">{s.userName}</div>
                            <div className="text-caption">{s.userEmail}</div>
                        </div>
                        <div className="flex items-center gap-3">
                            <span className="font-mono text-[11px] text-slate-500">{s.planName} — {formatPrice(s.priceMinor, s.currency)}</span>
                            <Badge variant="outline">{s.statusLabel}</Badge>
                            <span className="font-mono text-[11px] text-slate-400">
                                depuis {new Date(s.startsAt).toLocaleDateString('fr-FR')}
                            </span>
                        </div>
                    </div>
                ))}
            </div>

            {subscriptions.links.length > 3 && (
                <div className="mt-6 flex flex-wrap justify-center gap-1">
                    {subscriptions.links.map((link, i) => (
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
