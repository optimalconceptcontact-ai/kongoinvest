import { Head } from '@inertiajs/react';
import { AdminLayout } from '@/layouts/AdminLayout';

type Stats = {
    usersByRole: Record<string, number>;
    projectsByStatus: Record<string, number>;
    totalMatches: number;
    subscriptionsByPlan: Record<string, number>;
};

function StatCard({ label, value }: { label: string; value: number | string }) {
    return (
        <div className="border border-slate-200 p-5">
            <div className="text-stat">{value}</div>
            <div className="mt-1 text-caption">{label}</div>
        </div>
    );
}

export default function AdminDashboard({ stats }: { stats: Stats }) {
    return (
        <AdminLayout title="Dashboard">
            <Head title="Admin — Dashboard" />

            <div className="mb-8 border border-slate-200 bg-slate-50 px-4 py-3 text-caption">
                Les revenus, paiements, fonds libérés et litiges apparaîtront ici une fois les Phases 10-11
                (paiements, Escrow) construites — cette page n'affiche que des données réelles.
            </div>

            <h2 className="mb-3 font-mono text-xs uppercase tracking-widest text-slate-400">Utilisateurs</h2>
            <div className="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <StatCard label="Entrepreneurs" value={stats.usersByRole.entrepreneur ?? 0} />
                <StatCard label="Investisseurs" value={stats.usersByRole.investor ?? 0} />
                <StatCard label="Admins" value={stats.usersByRole.admin ?? 0} />
            </div>

            <h2 className="mb-3 font-mono text-xs uppercase tracking-widest text-slate-400">Projets</h2>
            <div className="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-3">
                <StatCard label="Brouillons" value={stats.projectsByStatus.draft ?? 0} />
                <StatCard label="Publiés" value={stats.projectsByStatus.published ?? 0} />
                <StatCard label="Archivés" value={stats.projectsByStatus.archived ?? 0} />
            </div>

            <h2 className="mb-3 font-mono text-xs uppercase tracking-widest text-slate-400">Matching & Abonnements</h2>
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <StatCard label="Matches calculés" value={stats.totalMatches} />
                {Object.entries(stats.subscriptionsByPlan).map(([key, count]) => (
                    <StatCard key={key} label={`Plan ${key}`} value={count as number} />
                ))}
            </div>
        </AdminLayout>
    );
}
