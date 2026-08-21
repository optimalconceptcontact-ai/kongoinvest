import { router } from '@inertiajs/react';
import { RiNotification3Line } from '@remixicon/react';
import { useEffect, useState } from 'react';

type NotificationItem = {
    id: string;
    type: string | null;
    data: Record<string, any>;
    readAt: string | null;
    createdAt: string;
};

const typeLabels: Record<string, string> = {
    new_match: 'Nouveau match',
    connection_request: 'Demande de connexion',
    connection_accepted: 'Connexion acceptée',
    payment_succeeded: 'Paiement confirmé',
    escrow_funded: 'Séquestre financé',
    escrow_released: 'Fonds libérés',
    escrow_refunded: 'Remboursement effectué',
    escrow_disputed: 'Litige ouvert',
    kyc_approved: 'KYC approuvé',
    kyc_rejected: 'KYC rejeté',
    contract_accepted: 'Contrat accepté',
};

export function NotificationBell() {
    const [open, setOpen] = useState(false);
    const [notifications, setNotifications] = useState<NotificationItem[]>([]);
    const [unreadCount, setUnreadCount] = useState(0);

    async function load() {
        const res = await fetch('/notifications', { headers: { Accept: 'application/json' } });
        if (!res.ok) return;
        const json = await res.json();
        setNotifications(json.notifications);
        setUnreadCount(json.unreadCount);
    }

    useEffect(() => {
        load();
        const interval = setInterval(load, 60_000); // poll every minute — no websockets in this scaffold
        return () => clearInterval(interval);
    }, []);

    function openDropdown() {
        setOpen((v) => !v);
        if (!open) load();
    }

    function goTo(n: NotificationItem) {
        router.post(`/notifications/${n.id}/read`, {}, { preserveScroll: true, onSuccess: load });
        if (n.data.url) router.visit(n.data.url);
        setOpen(false);
    }

    return (
        <div className="relative">
            <button onClick={openDropdown} className="relative p-2 text-slate-500 hover:text-slate-900" aria-label="Notifications">
                <RiNotification3Line size={19} />
                {unreadCount > 0 && (
                    <span className="absolute right-1 top-1 flex h-2 w-2 rounded-full bg-orange-500" />
                )}
            </button>

            {open && (
                <div className="absolute right-0 z-50 mt-2 w-80 border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-slate-200 px-3 py-2">
                        <span className="font-mono text-[11px] uppercase text-slate-500">Notifications</span>
                        {unreadCount > 0 && (
                            <button
                                onClick={() => router.post('/notifications/read-all', {}, { preserveScroll: true, onSuccess: load })}
                                className="font-mono text-[11px] text-blue-600 hover:text-blue-700"
                            >
                                Tout marquer lu
                            </button>
                        )}
                    </div>
                    <div className="max-h-80 overflow-y-auto">
                        {notifications.length === 0 ? (
                            <p className="px-3 py-6 text-center text-caption">Aucune notification.</p>
                        ) : (
                            notifications.map((n) => (
                                <button
                                    key={n.id}
                                    onClick={() => goTo(n)}
                                    className={`block w-full border-b border-slate-100 px-3 py-2.5 text-left text-xs last:border-b-0 hover:bg-slate-50 ${!n.readAt ? 'bg-blue-50/50' : ''}`}
                                >
                                    <div className="font-semibold text-slate-900">{typeLabels[n.type ?? ''] ?? n.type}</div>
                                    {n.data.project_title && <div className="text-slate-500">{n.data.project_title}</div>}
                                    <div className="mt-0.5 font-mono text-[10px] text-slate-400">
                                        {new Date(n.createdAt).toLocaleDateString('fr-FR')}
                                    </div>
                                </button>
                            ))
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
