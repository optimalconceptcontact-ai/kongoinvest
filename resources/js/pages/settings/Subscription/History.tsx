import { Head } from '@inertiajs/react';
import { BackLink } from '@/components/ui/BackLink';
import { Badge } from '@/components/ui/badge';
import { Link } from '@/components/ui/Link';

type SubscriptionRow = {
    id: number;
    planName: string;
    billingPeriod: string;
    amountMinor: number;
    currency: string;
    status: string;
    statusLabel: string;
    startsAt: string | null;
    endsAt: string | null;
    paymentProvider: string | null;
};

const statusClasses: Record<string, string> = {
    active: 'bg-surface-2 text-ink ',
    trialing: 'bg-surface-2 text-ink ',
    past_due: 'bg-surface-2 text-ink ',
    canceled: 'bg-surface-2 text-ink-muted ',
    expired: 'bg-surface-2 text-ink-muted ',
};

function formatAmount(amountMinor: number, currency: string) {
    if (amountMinor === 0) {
        return 'Gratuit';
    }

    return `${(amountMinor / 100).toLocaleString('fr-FR')} ${currency}`;
}

function formatDate(iso: string | null) {
    if (!iso) {
        return '—';
    }

    return new Date(iso).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

export default function History({
    subscriptions,
}: {
    subscriptions: SubscriptionRow[];
}) {
    return (
        <div className="mx-auto max-w-5xl px-6 py-12">
            <Head title="Historique des abonnements" />
            <BackLink href="/settings/subscription">
                ← Retour à l'abonnement
            </BackLink>
            <p className="text-eyebrow">Paramètres</p>
            <div className="mt-2 mb-2 flex items-center justify-between">
                <h1 className="text-page-title">Historique des abonnements</h1>
            </div>
            <p className="mb-10 text-body">
                Tous les abonnements souscrits sur votre compte, du plus récent
                au plus ancien.
            </p>

            {subscriptions.length === 0 ? (
                <div className="border border-rule-strong bg-paper px-6 py-12 text-center text-body">
                    Aucun abonnement pour le moment.
                </div>
            ) : (
                <div className="border border-rule-strong bg-paper">
                    <div className="hidden grid-cols-[2fr_1fr_1fr_1fr_1fr] gap-4 border-b border-rule bg-surface-2 px-6 py-3 font-mono text-lg tracking-wide text-ink-muted uppercase md:grid">
                        <span>Plan</span>
                        <span>Période</span>
                        <span>Montant</span>
                        <span>Statut</span>
                        <span>Date</span>
                    </div>
                    {subscriptions.map((s) => (
                        <Link
                            key={s.id}
                            href={`/settings/subscription/history/${s.id}`}
                            className="grid grid-cols-2 items-center gap-2 border-b border-rule px-6 py-4 text-lg text-ink last:border-b-0 hover:bg-surface-2 md:grid-cols-[2fr_1fr_1fr_1fr_1fr] md:gap-4"
                        >
                            <span className="font-semibold">{s.planName}</span>
                            <span className="text-ink-muted">
                                {s.billingPeriod === 'annual'
                                    ? 'Annuel'
                                    : 'Mensuel'}
                            </span>
                            <span className="font-mono">
                                {formatAmount(s.amountMinor, s.currency)}
                            </span>
                            <span>
                                <Badge
                                    className={
                                        statusClasses[s.status] ??
                                        'bg-surface-2 text-ink-muted'
                                    }
                                >
                                    {s.statusLabel}
                                </Badge>
                            </span>
                            <span className="text-ink-muted">
                                {formatDate(s.startsAt)}
                            </span>
                        </Link>
                    ))}
                </div>
            )}
        </div>
    );
}
