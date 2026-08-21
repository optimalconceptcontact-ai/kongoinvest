import { Head, router } from '@inertiajs/react';
import { RiRefreshLine } from '@remixicon/react';
import { MatchCard, type MatchCardData } from '@/components/MatchCard';
import { Button } from '@/components/Ui/Button';

export default function Index({ matches }: { matches: MatchCardData[] }) {
    function recompute() {
        router.post('/investor/matches/recompute');
    }

    function markViewed(id: number) {
        router.post(`/investor/matches/${id}/viewed`, {}, { preserveScroll: true });
    }

    function dismiss(id: number) {
        router.post(`/investor/matches/${id}/dismiss`, {}, { preserveScroll: true });
    }

    return (
        <>
            <Head title="Mes matches" />
            <div className="mx-auto max-w-3xl px-6 py-12">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <p className="text-eyebrow">Investisseur</p>
                        <h1 className="mt-2 text-page-title">Projets recommandés par l'IA</h1>
                    </div>
                    <Button variant="outline" size="sm" onClick={recompute} className="flex items-center gap-1.5 py-2">
                        <RiRefreshLine size={14} /> Recalculer
                    </Button>
                </div>

                <p className="mb-8 border border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-700">
                    Analyse générée par IA. Ces informations sont fournies à titre indicatif uniquement et ne
                    constituent pas un conseil financier, juridique ou d'investissement.
                </p>

                {matches.length === 0 ? (
                    <div className="border border-dashed border-slate-300 py-16 text-center text-body">
                        Aucun match pour l'instant. Complétez votre profil d'investissement pour être mis en
                        relation avec des projets pertinents.
                    </div>
                ) : (
                    <div className="flex flex-col gap-4">
                        {matches.map((match) => (
                            <MatchCard
                                key={match.id}
                                match={match}
                                title={match.projectTitle}
                                subtitle={`Calculé le ${new Date(match.computedAt).toLocaleDateString('fr-FR')}`}
                                href={`/projects/${match.projectSlug}`}
                                actions={
                                    <>
                                        {match.status === 'pending' && (
                                            <Button variant="outline" size="sm" onClick={() => markViewed(match.id)}>
                                                Marquer comme consulté
                                            </Button>
                                        )}
                                        {match.status !== 'dismissed' && (
                                            <button
                                                onClick={() => dismiss(match.id)}
                                                className="border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-500 hover:border-orange-600 hover:text-orange-600"
                                            >
                                                Ignorer
                                            </button>
                                        )}
                                    </>
                                }
                            />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
