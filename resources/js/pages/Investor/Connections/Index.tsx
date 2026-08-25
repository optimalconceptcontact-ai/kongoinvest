import { Head, Link as InertiaLink, router } from '@inertiajs/react';
import { InvestorLayout } from '@/layouts/InvestorLayout';
import { Link } from '@/components/Ui/Link';

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
    pending: 'bg-orange-50 text-orange-700',
    accepted: 'bg-blue-50 text-blue-700',
    rejected: 'bg-slate-100 text-slate-400',
    cancelled: 'bg-slate-100 text-slate-400',
};

const statusLabels: Record<string, string> = {
    pending: 'En attente', accepted: 'Acceptée', rejected: 'Refusée', cancelled: 'Annulée',
};

export default function Index({ connections }: { connections: ConnectionRow[] }) {
    function cancel(id: number) {
        router.post(`/investor/connections/${id}/cancel`, {}, { preserveScroll: true });
    }

    return (
        <InvestorLayout title="Mes connexions">
            <Head title="Mes connexions" />

            {connections.length === 0 ? (
                <div className="border border-dashed border-slate-400 py-16 text-center text-body">
                    Aucune demande de connexion pour l'instant. Ouvrez un projet qui vous intéresse pour en
                    envoyer une.
                </div>
            ) : (
                <div className="border border-slate-200">
                    {connections.map((c) => (
                        <div key={c.id} className="flex items-center justify-between border-b border-slate-200 p-5 last:border-b-0">
                            <div>
                                <div className="mb-1 flex items-center gap-2">
                                    <span className={`px-2 py-0.5 font-mono text-[11px] uppercase ${statusStyles[c.status]}`}>
                                        {statusLabels[c.status]}
                                    </span>
                                    <span className="font-mono text-[11px] text-slate-400">
                                        {new Date(c.createdAt).toLocaleDateString('fr-FR')}
                                    </span>
                                </div>
                                <InertiaLink href={`/projects/${c.projectSlug}`} className="text-item-title hover:text-blue-700">
                                    {c.projectTitle}
                                </InertiaLink>
                                {c.message && <p className="mt-1 max-w-md text-caption">"{c.message}"</p>}
                            </div>
                            <div>
                                {c.status === 'accepted' && c.dealRoomId && (
                                    <Link href={`/deal-rooms/${c.dealRoomId}`} variant="secondary" size="sm" className="py-2">
                                        Ouvrir la Deal Room
                                    </Link>
                                )}
                                {c.status === 'pending' && (
                                    <button onClick={() => cancel(c.id)} className="border border-slate-400 px-3 py-2 text-xs font-semibold text-slate-500 hover:border-orange-600 hover:text-orange-600">
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
