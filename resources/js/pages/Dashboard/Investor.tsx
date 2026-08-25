import { Head, Link } from '@inertiajs/react';
import { InvestorLayout } from '@/layouts/InvestorLayout';

type Props = {
    matchCount: number;
    pendingMatchCount: number;
    topMatches: { id: number; score: number; status: string; projectTitle: string; projectSlug: string }[];
    profileComplete: boolean;
    publishedProjectCount: number;
    currentPlan: { key: string; name: string };
};

export default function InvestorDashboard({ matchCount, pendingMatchCount, topMatches, profileComplete, publishedProjectCount, currentPlan }: Props) {
    return (
        <InvestorLayout title="Dashboard">
            <Head title="Dashboard" />

            {!profileComplete && (
                <div className="mb-6 border border-orange-200 bg-orange-50 p-4 text-sm text-orange-700">
                    Complétez votre profil investisseur pour recevoir des recommandations pertinentes.{' '}
                    <Link href="/settings/investor-profile" className="font-semibold underline">Compléter maintenant</Link>
                </div>
            )}

            <div className="mb-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
                <div className="border border-slate-300 p-6">
                    <div className="text-stat">{matchCount}</div>
                    <div className="mt-1 text-caption">Matches au total</div>
                </div>
                <div className="border border-slate-300 p-6">
                    <div className="font-mono text-2xl font-bold text-orange-600">{pendingMatchCount}</div>
                    <div className="mt-1 text-caption">Nouveaux matches</div>
                </div>
                <div className="border border-slate-300 p-6">
                    <div className="text-stat">{publishedProjectCount}</div>
                    <div className="mt-1 text-caption">Projets publiés sur la plateforme</div>
                </div>
            </div>

            <div className="border border-slate-300 p-6">
                <div className="mb-4 flex items-center justify-between">
                    <h2 className="text-section-label">Meilleurs matches</h2>
                    <Link href="/investor/matches" className="font-mono text-xs text-blue-600 hover:text-blue-700">Voir tout →</Link>
                </div>
                {topMatches.length === 0 ? (
                    <p className="text-body">Aucun match pour l'instant — complétez votre profil ou explorez les projets.</p>
                ) : (
                    <div className="flex flex-col gap-2">
                        {topMatches.map((m) => (
                            <Link key={m.id} href={`/projects/${m.projectSlug}`} className="flex items-center justify-between border-b border-slate-100 py-2 text-sm last:border-b-0 hover:text-blue-700">
                                <span className="text-slate-700">{m.projectTitle}</span>
                                <span className="font-mono font-bold text-blue-600">{m.score}</span>
                            </Link>
                        ))}
                    </div>
                )}
            </div>

            <div className="mt-6 border border-slate-300 p-6">
                <div className="font-mono text-[11px] uppercase text-slate-500">Plan actuel</div>
                <div className="mt-1 text-item-title-lg">{currentPlan.name}</div>
                <Link href="/settings/subscription" className="mt-3 inline-block font-mono text-xs text-blue-600 hover:text-blue-700">
                    Gérer l'abonnement →
                </Link>
            </div>
        </InvestorLayout>
    );
}
