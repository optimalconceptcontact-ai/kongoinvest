import { Head, Link } from '@inertiajs/react';

/**
 * Spec §137: a clean UX for 401/403/404/419/422/429/500 — never a raw
 * stack trace. One component handles every status; only the copy
 * changes. bootstrap/app.php's exception renderer is what decides which
 * status reaches here and passes it as a prop.
 */
const copy: Record<number, { title: string; body: string }> = {
    401: {
        title: 'Non authentifié',
        body: 'Vous devez vous connecter pour accéder à cette page.',
    },
    403: {
        title: 'Accès refusé',
        body: "Vous n'avez pas la permission d'accéder à cette page.",
    },
    404: {
        title: 'Page introuvable',
        body: "Cette page n'existe pas ou a été déplacée.",
    },
    419: {
        title: 'Session expirée',
        body: 'Votre session a expiré. Veuillez réessayer.',
    },
    422: {
        title: 'Requête invalide',
        body: 'Les données envoyées ne sont pas valides.',
    },
    429: {
        title: 'Trop de requêtes',
        body: 'Vous avez effectué trop de requêtes. Réessayez dans quelques instants.',
    },
    500: {
        title: 'Erreur serveur',
        body: "Une erreur inattendue s'est produite. Notre équipe a été notifiée.",
    },
    503: {
        title: 'Maintenance en cours',
        body: 'KongoInvest est temporairement indisponible. Revenez dans quelques instants.',
    },
};

export default function Error({ status }: { status: number }) {
    const { title, body } = copy[status] ?? {
        title: 'Erreur',
        body: "Une erreur s'est produite.",
    };

    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-page px-4 text-center">
            <Head title={title} />
            <div className="w-full max-w-md border border-rule-strong bg-paper px-6 py-12">
                <div className="font-mono text-9xl font-bold tracking-[-0.04em] text-ink">
                    {status}
                </div>
                <div className="mx-auto my-6 h-1 w-12 bg-signal" />
                <h1 className="mb-2 text-page-title">{title}</h1>
                <p className="mx-auto mb-8 max-w-sm text-body">{body}</p>
                <Link
                    href="/"
                    className="inline-flex min-h-11 items-center border border-ink bg-ink px-6 py-2.5 font-mono text-lg font-bold tracking-[0.06em] text-paper capitalize transition-opacity hover:opacity-85"
                >
                    Retour à l'accueil
                </Link>
            </div>
        </div>
    );
}
