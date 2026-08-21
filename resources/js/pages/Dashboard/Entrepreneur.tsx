import { Head, Link } from '@inertiajs/react';
import { RiAddLine, RiArrowRightLine } from '@remixicon/react';
import { EntrepreneurLayout } from '@/layouts/EntrepreneurLayout';

type Props = {
    activeProject: { title: string; slug: string; status: string; fundingTargetMinor: number | null; fundingRaisedMinor: number | null; fundingCurrency: string } | null;
    canCreateProject: boolean;
    matchCount: number;
    topMatches: { id: number; score: number; investorName: string }[];
    currentPlan: { key: string; name: string };
};

export default function EntrepreneurDashboard({ activeProject, canCreateProject, matchCount, topMatches, currentPlan }: Props) {
    return (
        <EntrepreneurLayout title="Dashboard">
            <Head title="Dashboard" />

            {!activeProject ? (
                <div className="border border-dashed border-slate-300 p-10 text-center">
                    <p className="mb-4 text-body">Vous n'avez pas encore de projet actif.</p>
                    {canCreateProject && (
                        <Link href="/entrepreneur/projects/create" className="inline-flex items-center gap-2 bg-orange-500 px-5 py-2.5 text-btn text-white hover:bg-orange-600">
                            <RiAddLine size={16} /> Créer mon projet
                        </Link>
                    )}
                </div>
            ) : (
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="border border-slate-200 p-6 lg:col-span-2">
                        <div className="mb-1 font-mono text-[11px] uppercase text-orange-600">{activeProject.status}</div>
                        <h2 className="mb-4 text-item-title-lg">{activeProject.title}</h2>
                        {activeProject.fundingTargetMinor && (
                            <div className="mb-4">
                                <div className="h-1.5 w-full bg-slate-100">
                                    <div
                                        className="h-full bg-blue-500"
                                        style={{ width: `${Math.min(100, ((activeProject.fundingRaisedMinor ?? 0) / activeProject.fundingTargetMinor) * 100)}%` }}
                                    />
                                </div>
                                <div className="mt-1 font-mono text-xs text-slate-500">
                                    {((activeProject.fundingRaisedMinor ?? 0) / 100).toLocaleString('fr-FR')} / {(activeProject.fundingTargetMinor / 100).toLocaleString('fr-FR')} {activeProject.fundingCurrency}
                                </div>
                            </div>
                        )}
                        <Link href={`/entrepreneur/projects`} className="inline-flex items-center gap-1.5 font-mono text-xs text-blue-600 hover:text-blue-700">
                            Gérer mon projet <RiArrowRightLine size={12} />
                        </Link>
                    </div>
                    <div className="border border-slate-200 p-6">
                        <div className="font-mono text-[11px] uppercase text-slate-500">Plan actuel</div>
                        <div className="mt-1 text-item-title-lg">{currentPlan.name}</div>
                        <Link href="/settings/subscription" className="mt-3 inline-block font-mono text-xs text-blue-600 hover:text-blue-700">
                            Gérer l'abonnement →
                        </Link>
                    </div>
                </div>
            )}

            {activeProject && (
                <div className="mt-6 border border-slate-200 p-6">
                    <div className="mb-4 flex items-center justify-between">
                        <h2 className="text-section-label">Investisseurs matchés ({matchCount})</h2>
                        <Link href="/entrepreneur/matches" className="font-mono text-xs text-blue-600 hover:text-blue-700">Voir tout →</Link>
                    </div>
                    {topMatches.length === 0 ? (
                        <p className="text-body">Aucun match pour l'instant.</p>
                    ) : (
                        <div className="flex flex-col gap-2">
                            {topMatches.map((m) => (
                                <div key={m.id} className="flex items-center justify-between border-b border-slate-100 py-2 text-sm last:border-b-0">
                                    <span className="text-slate-700">{m.investorName}</span>
                                    <span className="font-mono font-bold text-blue-600">{m.score}</span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            )}
        </EntrepreneurLayout>
    );
}
