import { Head, router } from '@inertiajs/react';
import { RiRefreshLine } from '@remixicon/react';
import { MatchCard } from '@/components/MatchCard';
import type { MatchCardData } from '@/components/MatchCard';
import { BackLink } from '@/components/ui/BackLink';
import { Button } from '@/components/ui/button';

export default function Index({ matches }: { matches: MatchCardData[] }) {
    function recompute() {
        router.post('/investor/matches/recompute');
    }

    function markViewed(id: number) {
        router.post(
            `/investor/matches/${id}/viewed`,
            {},
            { preserveScroll: true },
        );
    }

    function dismiss(id: number) {
        router.post(
            `/investor/matches/${id}/dismiss`,
            {},
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Mes matches" />
            <div className="mx-auto max-w-3xl px-6 py-12">
                <BackLink href="/dashboard" />
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <p className="text-eyebrow">Investisseur</p>
                        <h1 className="mt-2 text-page-title">
                            Projets recommandés par l'IA
                        </h1>
                    </div>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={recompute}
                        className="flex items-center gap-1.5 py-2"
                    >
                        <RiRefreshLine size={14} /> Recalculer
                    </Button>
                </div>

                <p className="mb-8 border border-rule-strong bg-surface-2 px-4 py-3 font-mono text-lg leading-5 text-ink-muted">
                    Analyse générée par IA. Ces informations sont fournies à
                    titre indicatif uniquement et ne constituent pas un conseil
                    financier, juridique ou d'investissement.
                </p>

                {matches.length === 0 ? (
                    <div className="border border-rule-strong bg-paper p-6 text-body">
                        Aucun match pour l'instant. Complétez votre profil
                        d'investissement pour être mis en relation avec des
                        projets pertinents.
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
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    markViewed(match.id)
                                                }
                                            >
                                                Marquer comme consulté
                                            </Button>
                                        )}
                                        {match.status !== 'dismissed' && (
                                            <button
                                                onClick={() =>
                                                    dismiss(match.id)
                                                }
                                                className="min-h-10 border border-rule-strong px-3 py-1.5 font-mono text-lg font-bold tracking-[0.06em] text-ink-muted capitalize hover:border-ink hover:text-ink"
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
