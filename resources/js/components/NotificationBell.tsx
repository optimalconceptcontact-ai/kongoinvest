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
    subscription_expiring: 'Expiration proche',
    subscription_grace_reminder: "Renouvellement d'abonnement",
    subscription_grace_started: 'Abonnement expiré',
    subscription_downgraded: 'Retour au plan Free',
    subscription_canceled: 'Abonnement annulé',
    subscription_past_due: 'Paiement échoué',
};

export function NotificationBell({
    align = 'right',
}: { align?: 'left' | 'right' } = {}) {
    const [open, setOpen] = useState(false);
    const [notifications, setNotifications] = useState<NotificationItem[]>([]);
    const [unreadCount, setUnreadCount] = useState(0);

    async function load() {
        const res = await fetch('/notifications', {
            headers: { Accept: 'application/json' },
        });

        if (!res.ok) {
            return;
        }

        const json = await res.json();
        setNotifications(json.notifications);
        setUnreadCount(json.unreadCount);
    }

    useEffect(() => {
        // Initial fetch deferred off the synchronous effect path
        // (react-hooks/set-state-in-effect); the interval polls every
        // minute after that — no websockets in this scaffold.
        const kickoff = setTimeout(load, 0);
        const interval = setInterval(load, 60_000);

        return () => {
            clearTimeout(kickoff);
            clearInterval(interval);
        };
    }, []);

    function openDropdown() {
        setOpen((v) => !v);

        if (!open) {
            load();
        }
    }

    function goTo(n: NotificationItem) {
        setOpen(false);
        const url = typeof n.data.url === 'string' ? n.data.url : undefined;

        // Serialize, never race: the mark-read visit must finish before
        // router.visit() starts — two concurrent Inertia visits fight over
        // navigation and can bounce the user back to the previous page.
        // onFinish fires on success AND failure, so navigation always
        // happens even if marking read fails.
        router.post(
            `/notifications/${n.id}/read`,
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    if (url) {
                        router.visit(url);
                    } else {
                        load();
                    }
                },
            },
        );
    }

    return (
        <div className="relative">
            <button
                onClick={openDropdown}
                className="relative flex h-11 w-11 items-center justify-center text-ink-muted hover:text-ink"
                aria-label="Notifications"
            >
                <RiNotification3Line size={19} />
                {unreadCount > 0 && (
                    <span className="absolute top-1.5 right-1.5 flex h-2.5 w-2.5 bg-signal ring-2 ring-paper" />
                )}
            </button>

            {open && (
                <div
                    className={`absolute z-50 mt-2 w-80 max-w-[calc(100vw-2rem)] border border-rule-strong bg-paper shadow-[4px_4px_0_0_rgba(14,23,38,0.08)] dark:shadow-[4px_4px_0_0_rgba(0,0,0,0.35)] ${
                        align === 'left' ? 'left-0' : 'right-0'
                    }`}
                >
                    <div className="flex items-center justify-between border-b border-rule px-3 py-2">
                        <span className="font-mono text-lg font-medium tracking-[0.16em] text-ink-muted uppercase">
                            Notifications
                        </span>
                        {unreadCount > 0 && (
                            <button
                                onClick={() =>
                                    router.post(
                                        '/notifications/read-all',
                                        {},
                                        {
                                            preserveScroll: true,
                                            onSuccess: load,
                                        },
                                    )
                                }
                                className="min-h-11 px-2 font-mono text-lg text-action hover:underline"
                            >
                                Tout marquer lu
                            </button>
                        )}
                    </div>
                    <div className="max-h-80 overflow-y-auto">
                        {notifications.length === 0 ? (
                            <p className="px-3 py-6 text-center text-caption">
                                Aucune notification.
                            </p>
                        ) : (
                            notifications.map((n) => (
                                <button
                                    key={n.id}
                                    onClick={() => goTo(n)}
                                    className={`block min-h-11 w-full border-b border-l-2 border-rule border-l-transparent px-3 py-2.5 text-left text-lg last:border-b-0 hover:bg-surface-2 ${!n.readAt ? 'border-l-signal bg-surface-2' : ''}`}
                                >
                                    <div className="font-semibold text-ink">
                                        {typeLabels[n.type ?? ''] ?? n.type}
                                    </div>
                                    {n.data.project_title && (
                                        <div className="text-ink-muted">
                                            {n.data.project_title}
                                        </div>
                                    )}
                                    <div className="mt-0.5 font-mono text-lg text-ink-muted">
                                        {new Date(
                                            n.createdAt,
                                        ).toLocaleDateString('fr-FR')}
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
