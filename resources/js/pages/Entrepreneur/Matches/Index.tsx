import { Head } from '@inertiajs/react';
import { MatchCard, type MatchCardData } from '@/components/MatchCard';

export default function Index({ matches }: { matches: MatchCardData[] }) {
    return (
        <>
            <Head title="Investisseurs intéressés" />
            <div className="mx-auto max-w-3xl px-6 py-12">
                <p className="font-mono text-xs uppercase tracking-widest text-orange-600">Entrepreneur</p>
                <h1 className="mt-2 mb-2 text-2xl font-extrabold text-slate-900">Investisseurs matchés par l'IA</h1>
                <p className="mb-8 text-sm text-slate-500">
                    Ce classement se met à jour automatiquement à chaque publication ou mise à jour de votre
                    projet, et à chaque fois qu'un investisseur affine ses préférences.
                </p>

                {matches.length === 0 ? (
                    <div className="border border-dashed border-slate-300 py-16 text-center text-sm text-slate-500">
                        Aucun investisseur matché pour l'instant. Publiez votre projet pour déclencher le calcul
                        des matches.
                    </div>
                ) : (
                    <div className="flex flex-col gap-4">
                        {matches.map((match) => (
                            <MatchCard
                                key={match.id}
                                match={match}
                                title={match.investorName}
                                subtitle={`Pour « ${match.projectTitle} » — calculé le ${new Date(match.computedAt).toLocaleDateString('fr-FR')}`}
                            />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
