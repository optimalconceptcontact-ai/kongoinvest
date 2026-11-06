import { Head, Link } from '@inertiajs/react';
import { InvestorLayout } from '@/layouts/InvestorLayout';

type Props = {
    matchCount: number;
    pendingMatchCount: number;
    topMatches: {
        id: number;
        score: number;
        status: string;
        projectTitle: string;
        projectSlug: string;
    }[];
    profileComplete: boolean;
    publishedProjectCount: number;
    currentPlan: { key: string; name: string };
};

export default function InvestorDashboard({
    matchCount,
    pendingMatchCount,
    topMatches,
    profileComplete,
    publishedProjectCount,
    currentPlan,
}: Props) {
    return (
        <InvestorLayout title="Dashboard">
            <Head title="Dashboard" />

            {!profileComplete && (
                <div className="mb-6 flex flex-wrap items-center justify-between gap-3 border border-signal bg-signal/15 p-4 text-xl text-ink">
                    <span>
                        Complétez votre profil investisseur pour recevoir des
                        recommandations pertinentes.
                    </span>
                    <Link
                        href="/settings/investor-profile"
                        className="font-semibold text-action underline underline-offset-4"
                    >
                        Compléter maintenant
                    </Link>
                </div>
            )}

            <div className="mb-6 grid grid-cols-1 divide-y divide-rule border border-rule-strong bg-paper sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                <div className="p-6">
                    <div className="text-stat">{matchCount}</div>
                    <div className="mt-1 text-caption">Matches au total</div>
                </div>
                <div className="p-6">
                    <div className="text-stat">{pendingMatchCount}</div>
                    <div className="mt-1 text-caption">Nouveaux matches</div>
                </div>
                <div className="p-6">
                    <div className="text-stat">{publishedProjectCount}</div>
                    <div className="mt-1 text-caption">
                        Projets publiés sur la plateforme
                    </div>
                </div>
            </div>

            <div className="border border-rule-strong bg-paper p-6">
                <div className="mb-4 flex items-center justify-between">
                    <h2 className="text-section-label">Meilleurs matches</h2>
                    <Link
                        href="/investor/matches"
                        className="font-mono text-lg text-action hover:underline"
                    >
                        Voir tout →
                    </Link>
                </div>
                {topMatches.length === 0 ? (
                    <p className="text-body">
                        Aucun match pour l'instant — complétez votre profil ou
                        explorez les projets.
                    </p>
                ) : (
                    <div className="flex flex-col">
                        {topMatches.map((m) => (
                            <Link
                                key={m.id}
                                href={`/projects/${m.projectSlug}`}
                                className="flex min-h-11 items-center justify-between gap-4 border-b border-rule py-2 text-xl last:border-b-0 hover:underline"
                            >
                                <span className="text-ink">
                                    {m.projectTitle}
                                </span>
                                <span className="font-mono font-semibold text-ink">
                                    {m.score}
                                </span>
                            </Link>
                        ))}
                    </div>
                )}
            </div>

            <div className="mt-6 border border-rule-strong bg-paper p-6">
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
        </InvestorLayout>
    );
}
