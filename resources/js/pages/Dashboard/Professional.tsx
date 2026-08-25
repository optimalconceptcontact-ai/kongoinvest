import { Head, Link } from '@inertiajs/react';

export default function ProfessionalDashboard({ currentPlan, profileComplete }: { currentPlan: { key: string; name: string }; profileComplete: boolean }) {
    return (
        <div className="mx-auto max-w-2xl px-6 py-16">
            <Head title="Dashboard" />
            <p className="font-mono text-xs uppercase tracking-widest text-orange-600">Professionnel</p>
            <h1 className="mt-2 mb-6 text-2xl font-extrabold text-slate-900">Bienvenue.</h1>

            <p className="mb-8 border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                L'essentiel des fonctionnalités Professional — participation aux Deal Rooms, accès aux
                documents, Escrow et contrats (spec §32, §130) — dépend de phases qui ne sont pas encore
                construites. Ce tableau de bord s'enrichira au fur et à mesure.
            </p>

            {!profileComplete && (
                <div className="mb-6 border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-700">
                    Votre profil professionnel n'est pas encore complété.
                </div>
            )}

            <div className="border border-slate-300 p-6">
                <div className="font-mono text-[11px] uppercase text-slate-500">Plan actuel</div>
                <div className="mt-1 text-lg font-bold text-slate-900">{currentPlan.name}</div>
                <Link href="/settings/subscription" className="mt-3 inline-block font-mono text-xs text-blue-600 hover:text-blue-700">
                    Gérer l'abonnement →
                </Link>
            </div>
        </div>
    );
}
