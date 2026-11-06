import { Head } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Link } from '@/components/ui/Link';

type SubscriptionDetail = {
    id: number;
    planName: string;
    planKey: string;
    billingPeriod: string;
    amountMinor: number;
    currency: string;
    status: string;
    statusLabel: string;
    startsAt: string | null;
    endsAt: string | null;
    canceledAt: string | null;
    paymentProvider: string | null;
    providerSubscriptionId: string | null;
};

type Customer = { name: string; email: string };

const statusClasses: Record<string, string> = {
    active: 'bg-surface-2 text-ink ',
    trialing: 'bg-surface-2 text-ink ',
    past_due: 'bg-surface-2 text-ink ',
    canceled: 'bg-surface-2 text-ink-muted ',
    expired: 'bg-surface-2 text-ink-muted ',
};

const providerLabels: Record<string, string> = {
    stripe: 'Carte bancaire (Stripe)',
    paypal: 'PayPal',
    orange_money: 'Orange Money',
    mpesa: 'M-Pesa',
    airtel_money: 'Airtel Money',
};

function formatAmount(amountMinor: number, currency: string) {
    if (amountMinor === 0) {
        return 'Gratuit';
    }

    return `${(amountMinor / 100).toLocaleString('fr-FR', { minimumFractionDigits: 2 })} ${currency}`;
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

export default function Invoice({
    subscription,
    customer,
}: {
    subscription: SubscriptionDetail;
    customer: Customer;
}) {
    return (
        <div className="mx-auto max-w-2xl px-6 py-12">
            <Head title={`Facture #${subscription.id}`} />

            <div className="mb-6 flex items-center justify-between print:hidden">
                <Link href="/settings/subscription/history">
                    ← Retour à l'historique
                </Link>
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => window.print()}
                >
                    Imprimer
                </Button>
            </div>

            <div className="border border-rule-strong bg-paper p-8 print:border-0 print:p-0">
                <div className="mb-8 flex items-start justify-between border-b border-rule pb-6">
                    <div>
                        <div className="font-mono text-2xl font-bold text-ink">
                            KongoInvest
                        </div>
                        <p className="text-caption">
                            La plateforme d'investissement propulsée par l'IA,
                            entre la RDC et le monde.
                        </p>
                    </div>
                    <div className="text-right">
                        <div className="text-eyebrow">Facture</div>
                        <div className="font-mono text-lg text-ink-muted">
                            #{subscription.id}
                        </div>
                    </div>
                </div>

                <div className="mb-8 grid grid-cols-2 gap-6">
                    <div>
                        <div className="mb-1 font-mono text-lg tracking-wide text-ink-muted uppercase">
                            Facturé à
                        </div>
                        <div className="font-semibold text-ink">
                            {customer.name}
                        </div>
                        <div className="text-lg text-ink-muted">
                            {customer.email}
                        </div>
                    </div>
                    <div className="text-right">
                        <div className="mb-1 font-mono text-lg tracking-wide text-ink-muted uppercase">
                            Statut
                        </div>
                        <Badge
                            className={
                                statusClasses[subscription.status] ??
                                'bg-surface-2 text-ink-muted'
                            }
                        >
                            {subscription.statusLabel}
                        </Badge>
                    </div>
                </div>

                <table className="mb-8 w-full text-lg">
                    <thead>
                        <tr className="border-b border-rule text-left font-mono text-lg tracking-wide text-ink-muted uppercase">
                            <th className="pb-2 font-normal">Description</th>
                            <th className="pb-2 text-right font-normal">
                                Montant
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr className="border-b border-rule">
                            <td className="py-3">
                                Abonnement {subscription.planName} —{' '}
                                {subscription.billingPeriod === 'annual'
                                    ? 'facturation annuelle'
                                    : 'facturation mensuelle'}
                            </td>
                            <td className="py-3 text-right font-mono">
                                {formatAmount(
                                    subscription.amountMinor,
                                    subscription.currency,
                                )}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td className="pt-3 text-right font-semibold">
                                Total
                            </td>
                            <td className="pt-3 text-right font-mono font-bold text-ink">
                                {formatAmount(
                                    subscription.amountMinor,
                                    subscription.currency,
                                )}
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <div className="grid grid-cols-2 gap-6 border-t border-rule pt-6 text-lg">
                    <div>
                        <div className="mb-1 font-mono text-lg tracking-wide text-ink-muted uppercase">
                            Date de début
                        </div>
                        <div className="text-ink">
                            {formatDate(subscription.startsAt)}
                        </div>
                    </div>
                    <div>
                        <div className="mb-1 font-mono text-lg tracking-wide text-ink-muted uppercase">
                            Moyen de paiement
                        </div>
                        <div className="text-ink">
                            {subscription.paymentProvider
                                ? (providerLabels[
                                      subscription.paymentProvider
                                  ] ?? subscription.paymentProvider)
                                : '—'}
                        </div>
                    </div>
                    {subscription.endsAt && (
                        <div>
                            <div className="mb-1 font-mono text-lg tracking-wide text-ink-muted uppercase">
                                Date de fin
                            </div>
                            <div className="text-ink">
                                {formatDate(subscription.endsAt)}
                            </div>
                        </div>
                    )}
                    {subscription.canceledAt && (
                        <div>
                            <div className="mb-1 font-mono text-lg tracking-wide text-ink-muted uppercase">
                                Annulé le
                            </div>
                            <div className="text-ink">
                                {formatDate(subscription.canceledAt)}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
