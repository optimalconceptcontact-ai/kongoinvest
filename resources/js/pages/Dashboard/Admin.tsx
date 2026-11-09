import { Head, Link } from '@inertiajs/react';
import { AdminLayout } from '@/layouts/AdminLayout';

type Stats = {
    usersByRole: Record<string, number>;
    projectsByStatus: Record<string, number>;
    totalMatches: number;
    subscriptionsByPlan: Record<string, number>;
    investments: {
        funded: number;
        held: number;
        disputed: number;
        contractsAccepted: number;
    };
    revenue: {
        succeededPayments: number;
        byCurrency: Record<string, number>;
    };
    canViewStatistics: boolean;
};

function formatCount(value: number): string {
    return new Intl.NumberFormat('fr-FR').format(value);
}

function formatMoney(value: number, currency: string): string {
    return `${new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value)} ${currency}`;
}

function StatCard({
    label,
    value,
    href,
    currency,
}: {
    label: string;
    value: number | string;
    href?: string;
    currency?: string;
}) {
    const display =
        currency !== undefined
            ? formatMoney(Number(value), currency)
            : typeof value === 'number'
              ? formatCount(value)
              : value;

    const body = (
        <>
            <div className="text-stat">{display}</div>
            <div className="mt-1 text-caption">{label}</div>
        </>
    );

    if (href === undefined) {
        return (
            <div className="border border-rule-strong bg-paper p-5">
                {body}
            </div>
        );
    }

    return (
        <Link
            href={href}
            className="block border border-rule-strong bg-paper p-5 transition-colors hover:bg-surface-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-signal"
        >
            {body}
        </Link>
    );
}

export default function AdminDashboard({ stats }: { stats: Stats }) {
    // Each card opens the analytics page that mirrors its figure. When the
    // administrator cannot view statistics, it falls back to the operational
    // page instead of linking somewhere forbidden.
    const statistics = (path: string, fallback: string): string =>
        stats.canViewStatistics ? path : fallback;

    return (
        <AdminLayout title="Dashboard">
            <Head title="Admin — Dashboard" />

            <div className="mb-8 border border-l-4 border-rule border-l-signal bg-surface-2 px-4 py-3 text-caption">
                Toutes les cartes proviennent de la base KongoInvest. Les fonds
                d'escrow ne sont jamais comptés comme revenus : les revenus
                affichés proviennent uniquement du grand livre des paiements
                d'abonnement.
            </div>

            <h2 className="mb-3 text-section-label">Utilisateurs</h2>
            <div className="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-3">
                <StatCard
                    label="Entrepreneurs"
                    value={stats.usersByRole.entrepreneur ?? 0}
                    href={statistics(
                        '/admin/statistics/entrepreneurs',
                        '/admin/users',
                    )}
                />
                <StatCard
                    label="Investisseurs"
                    value={stats.usersByRole.investor ?? 0}
                    href={statistics(
                        '/admin/statistics/investors',
                        '/admin/users',
                    )}
                />
                <StatCard
                    label="Admins"
                    value={stats.usersByRole.admin ?? 0}
                    href="/admin/users"
                />
            </div>

            <h2 className="mb-3 text-section-label">Projets</h2>
            <div className="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <StatCard
                    label="Brouillons"
                    value={stats.projectsByStatus.draft ?? 0}
                    href={statistics(
                        '/admin/statistics/projects?status=draft',
                        '/admin/projects?status=draft',
                    )}
                />
                <StatCard
                    label="En validation"
                    value={stats.projectsByStatus.pending_review ?? 0}
                    href={statistics(
                        '/admin/statistics/projects?status=pending_review',
                        '/admin/projects?status=pending_review',
                    )}
                />
                <StatCard
                    label="Publiés"
                    value={stats.projectsByStatus.published ?? 0}
                    href={statistics(
                        '/admin/statistics/projects?status=published',
                        '/admin/projects?status=published',
                    )}
                />
                <StatCard
                    label="Rejetés"
                    value={stats.projectsByStatus.rejected ?? 0}
                    href={statistics(
                        '/admin/statistics/projects?status=rejected',
                        '/admin/projects?status=rejected',
                    )}
                />
                <StatCard
                    label="Archivés"
                    value={stats.projectsByStatus.archived ?? 0}
                    href={statistics(
                        '/admin/statistics/projects?status=archived',
                        '/admin/projects?status=archived',
                    )}
                />
                <StatCard
                    label="Clôturés"
                    value={stats.projectsByStatus.closed ?? 0}
                    href={statistics(
                        '/admin/statistics/projects?status=closed',
                        '/admin/projects?status=closed',
                    )}
                />
            </div>

            <h2 className="mb-3 text-section-label">Investissements & contrats</h2>
            <div className="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <StatCard
                    label="Investissements réalisés"
                    value={stats.investments.funded}
                    href={statistics(
                        '/admin/statistics/investments',
                        '/admin/escrow',
                    )}
                />
                <StatCard
                    label="Fonds sous séquestre"
                    value={stats.investments.held}
                    href={statistics(
                        '/admin/statistics/investments?status=held',
                        '/admin/escrow',
                    )}
                />
                <StatCard
                    label="Escrows en litige"
                    value={stats.investments.disputed}
                    href={statistics(
                        '/admin/statistics/investments?status=disputed',
                        '/admin/escrow',
                    )}
                />
                <StatCard
                    label="Contrats acceptés"
                    value={stats.investments.contractsAccepted}
                    href="/admin/contracts"
                />
            </div>

            <h2 className="mb-3 text-section-label">Revenus</h2>
            <div className="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <StatCard
                    label="Paiements réussis"
                    value={stats.revenue.succeededPayments}
                    href={statistics(
                        '/admin/statistics/revenue?status=succeeded',
                        '/admin/payments',
                    )}
                />
                {Object.entries(stats.revenue.byCurrency).map(
                    ([currency, amount]) => (
                        <StatCard
                            key={currency}
                            label={`Encaissements ${currency}`}
                            value={amount}
                            currency={currency}
                            href={statistics(
                                `/admin/statistics/revenue?currency=${encodeURIComponent(currency)}`,
                                '/admin/payments',
                            )}
                        />
                    ),
                )}
            </div>

            <h2 className="mb-3 text-section-label">
                Matching & abonnements
            </h2>
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <StatCard
                    label="Matches calculés"
                    value={stats.totalMatches}
                    href="/admin/matching-weights"
                />
                {Object.entries(stats.subscriptionsByPlan).map(
                    ([key, count]) => (
                        <StatCard
                            key={key}
                            label={`Plan ${key}`}
                            value={count}
                            href="/admin/subscriptions"
                        />
                    ),
                )}
            </div>
        </AdminLayout>
    );
}
