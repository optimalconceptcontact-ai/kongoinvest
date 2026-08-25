import { Head, Link } from '@inertiajs/react';

/**
 * Spec §137: a clean UX for 401/403/404/419/422/429/500 — never a raw
 * stack trace. One component handles every status; only the copy
 * changes. bootstrap/app.php's exception renderer is what decides which
 * status reaches here and passes it as a prop.
 */
const copy: Record<number, { title: string; body: string }> = {
    401: { title: 'Non authentifié', body: 'Vous devez vous connecter pour accéder à cette page.' },
    403: { title: 'Accès refusé', body: "Vous n'avez pas la permission d'accéder à cette page." },
    404: { title: 'Page introuvable', body: "Cette page n'existe pas ou a été déplacée." },
    419: { title: 'Session expirée', body: 'Votre session a expiré. Veuillez réessayer.' },
    422: { title: 'Requête invalide', body: 'Les données envoyées ne sont pas valides.' },
    429: { title: 'Trop de requêtes', body: 'Vous avez effectué trop de requêtes. Réessayez dans quelques instants.' },
    500: { title: 'Erreur serveur', body: "Une erreur inattendue s'est produite. Notre équipe a été notifiée." },
    503: { title: 'Maintenance en cours', body: 'KongoInvestment est temporairement indisponible. Revenez dans quelques instants.' },
};

export default function Error({ status }: { status: number }) {
    const { title, body } = copy[status] ?? { title: 'Erreur', body: "Une erreur s'est produite." };

    return (
        <div className="flex min-h-screen flex-col items-center justify-center px-6 text-center">
            <Head title={title} />
            <div className="mb-4 font-mono text-6xl font-bold text-slate-200">{status}</div>
            <h1 className="mb-2 text-xl font-extrabold text-slate-900">{title}</h1>
            <p className="mb-8 max-w-sm text-sm text-slate-500">{body}</p>
            <Link href="/" className="bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                Retour à l'accueil
            </Link>
        </div>
    );
}
