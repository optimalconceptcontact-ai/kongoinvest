import { Head, Link } from '@inertiajs/react';
import { RiAddLine, RiArrowRightLine } from '@remixicon/react';
import { EntrepreneurLayout } from '@/layouts/EntrepreneurLayout';

type Props = {
    activeProject: {
        title: string;
        slug: string;
        status: string;
        fundingTargetMinor: number | null;
        fundingRaisedMinor: number | null;
        fundingCurrency: string;
    } | null;
    canCreateProject: boolean;
    matchCount: number;
    topMatches: { id: number; score: number; investorName: string }[];
    currentPlan: { key: string; name: string };
};

export default function EntrepreneurDashboard({
    activeProject,
    canCreateProject,
    matchCount,
    topMatches,
    currentPlan,
}: Props) {
    return (
        <EntrepreneurLayout title="Dashboard">
            <Head title="Dashboard" />

            {!activeProject ? (
                <div className="border border-rule-strong bg-paper p-6">
                    <p className="mb-4 text-body">
                        Vous n'avez pas encore de projet actif.
                    </p>
                    {canCreateProject && (
                        <Link
                            href="/entrepreneur/projects/create"
                            className="inline-flex min-h-11 items-center gap-2 border border-ink bg-ink px-5 py-2.5 font-mono text-lg font-bold tracking-[0.06em] text-paper capitalize transition-opacity hover:opacity-85"
                        >
                            <RiAddLine size={16} /> Créer mon projet
                        </Link>
                    )}
                </div>
            ) : (
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="border border-rule-strong bg-paper p-6 lg:col-span-2">
                        <div className="mb-2 flex items-center gap-2 font-mono text-lg text-ink-muted uppercase">
                            <span className="h-2 w-2 bg-signal" />
                            {activeProject.status}
                        </div>
                        <h2 className="mb-4 text-item-title-lg">
                            {activeProject.title}
                        </h2>
                        {activeProject.fundingTargetMinor && (
                            <div className="mb-4">
                                <div className="h-1 w-full bg-surface-2">
                                    <div
                                        className="h-full bg-action"
                                        style={{
                                            width: `${Math.min(100, ((activeProject.fundingRaisedMinor ?? 0) / activeProject.fundingTargetMinor) * 100)}%`,
                                        }}
                                    />
                                </div>
                                <div className="mt-2 font-mono text-lg text-ink-muted">
                                    {(
                                        (activeProject.fundingRaisedMinor ??
                                            0) / 100
                                    ).toLocaleString('fr-FR')}{' '}
                                    /{' '}
                                    {(
                                        activeProject.fundingTargetMinor / 100
                                    ).toLocaleString('fr-FR')}{' '}
                                    {activeProject.fundingCurrency}
                                </div>
                            </div>
                        )}
                        <Link
                            href={`/entrepreneur/projects`}
                            className="inline-flex min-h-11 items-center gap-1.5 font-mono text-lg text-action hover:underline"
                        >
                            Gérer mon projet <RiArrowRightLine size={12} />
                        </Link>
                    </div>
                    <div className="border border-rule-strong bg-paper p-6">
                        <div className="font-mono text-lg text-ink-muted uppercase">
                            Plan actuel
                        </div>
                        <div className="mt-1 text-item-title-lg">
                            {currentPlan.name}
                        </div>
                        <Link
                            href="/settings/subscription"
                            className="mt-3 inline-block font-mono text-lg text-action hover:underline"
                        >
                            Gérer l'abonnement →
                        </Link>
                    </div>
                </div>
            )}

            {activeProject && (
                <div className="mt-6 border border-rule-strong bg-paper p-6">
                    <div className="mb-4 flex items-center justify-between">
                        <h2 className="text-section-label">
                            Investisseurs matchés ({matchCount})
                        </h2>
                        <Link
                            href="/entrepreneur/matches"
                            className="font-mono text-lg text-action hover:underline"
                        >
                            Voir tout →
                        </Link>
                    </div>
                    {topMatches.length === 0 ? (
                        <p className="text-body">Aucun match pour l'instant.</p>
                    ) : (
                        <div className="flex flex-col">
                            {topMatches.map((m) => (
                                <div
                                    key={m.id}
                                    className="flex min-h-11 items-center justify-between border-b border-rule py-2 text-xl last:border-b-0"
                                >
                                    <span className="text-ink">
                                        {m.investorName}
                                    </span>
                                    <span className="font-mono font-semibold text-ink">
                                        {m.score}
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            )}
        </EntrepreneurLayout>
    );
}
