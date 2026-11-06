import { Head, Link as InertiaLink, router } from '@inertiajs/react';
import { Link } from '@/components/ui/Link';
import { InvestorLayout } from '@/layouts/InvestorLayout';

type ConnectionRow = {
    id: number;
    status: string;
    message: string | null;
    projectTitle: string;
    projectSlug: string;
    createdAt: string;
    dealRoomId: number | null;
};

const statusStyles: Record<string, string> = {
    pending: 'bg-surface-2 text-ink ',
    accepted: 'bg-surface-2 text-ink ',
    rejected: 'bg-surface-2 text-ink-muted ',
    cancelled: 'bg-surface-2 text-ink-muted ',
};

const statusLabels: Record<string, string> = {
    pending: 'En attente',
    accepted: 'Acceptée',
    rejected: 'Refusée',
    cancelled: 'Annulée',
};

export default function Index({
    connections,
}: {
    connections: ConnectionRow[];
}) {
    function cancel(id: number) {
        router.post(
            `/investor/connections/${id}/cancel`,
            {},
            { preserveScroll: true },
        );
    }

    return (
        <InvestorLayout title="Mes connexions">
            <Head title="Mes connexions" />

            {connections.length === 0 ? (
                <div className="border border-rule-strong bg-paper p-6 text-body">
                    Aucune demande de connexion pour l'instant. Ouvrez un projet
                    qui vous intéresse pour en envoyer une.
                </div>
            ) : (
                <div className="border border-rule">
                    {connections.map((c) => (
                        <div
                            key={c.id}
                            className="flex items-center justify-between border-b border-rule p-5 last:border-b-0"
                        >
                            <div>
                                <div className="mb-1 flex items-center gap-2">
                                    <span
                                        className={`px-2 py-0.5 font-mono text-lg uppercase ${statusStyles[c.status]}`}
                                    >
                                        {statusLabels[c.status]}
                                    </span>
                                    <span className="font-mono text-lg text-ink-muted">
                                        {new Date(
                                            c.createdAt,
                                        ).toLocaleDateString('fr-FR')}
                                    </span>
                                </div>
                                <InertiaLink
                                    href={`/projects/${c.projectSlug}`}
                                    className="text-item-title hover:underline"
                                >
                                    {c.projectTitle}
                                </InertiaLink>
                                {c.message && (
                                    <p className="mt-1 max-w-md text-caption">
                                        "{c.message}"
                                    </p>
                                )}
                            </div>
                            <div>
                                {c.status === 'accepted' && c.dealRoomId && (
                                    <Link
                                        href={`/deal-rooms/${c.dealRoomId}`}
                                        variant="secondary"
                                        size="sm"
                                        className="py-2"
                                    >
                                        Ouvrir la Deal Room
                                    </Link>
                                )}
                                {c.status === 'pending' && (
                                    <button
                                        onClick={() => cancel(c.id)}
                                        className="border border-rule-strong px-3 py-2 text-btn text-ink-muted hover:border-ink hover:text-ink"
                                    >
                                        Annuler
                                    </button>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </InvestorLayout>
    );
}
