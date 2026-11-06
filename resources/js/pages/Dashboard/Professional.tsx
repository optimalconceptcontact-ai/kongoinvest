import { Head, Link } from '@inertiajs/react';

export default function ProfessionalDashboard({
    currentPlan,
    profileComplete,
}: {
    currentPlan: { key: string; name: string };
    profileComplete: boolean;
}) {
    return (
        <div className="mx-auto max-w-2xl px-4 py-16 md:px-6">
            <Head title="Dashboard" />
            <p className="font-mono text-lg text-action uppercase">
                Professionnel
            </p>
            <h1 className="mt-2 mb-6 text-page-title">Bienvenue.</h1>

            <p className="mb-8 border border-l-4 border-rule border-l-signal bg-surface-2 px-4 py-3 text-xl leading-6 text-ink-muted">
                L'essentiel des fonctionnalités Professional — participation aux
                Deal Rooms, accès aux documents, Escrow et contrats (spec §32,
                §130) — dépend de phases qui ne sont pas encore construites. Ce
                tableau de bord s'enrichira au fur et à mesure.
            </p>

            {!profileComplete && (
                <div className="mb-6 border border-signal bg-signal/15 px-4 py-3 text-xl text-ink">
                    Votre profil professionnel n'est pas encore complété.
                </div>
            )}

            <div className="border border-rule-strong bg-paper p-6">
                <div className="text-section-label">Plan actuel</div>
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
    );
}
