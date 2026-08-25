import { Head } from '@inertiajs/react';
import { Badge } from '@/components/Ui/Badge';
import { Button } from '@/components/Ui/Button';
import { Link } from '@/components/Ui/Link';

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
    active: 'bg-blue-50 text-blue-700',
    trialing: 'bg-blue-50 text-blue-700',
    past_due: 'bg-orange-50 text-orange-700',
    canceled: 'bg-slate-100 text-slate-500',
    expired: 'bg-slate-100 text-slate-500',
};

const providerLabels: Record<string, string> = {
    stripe: 'Carte bancaire (Stripe)',
    paypal: 'PayPal',
    orange_money: 'Orange Money',
    mpesa: 'M-Pesa',
    airtel_money: 'Airtel Money',
};

function formatAmount(amountMinor: number, currency: string) {
    if (amountMinor === 0) return 'Gratuit';
    return `${(amountMinor / 100).toLocaleString('fr-FR', { minimumFractionDigits: 2 })} ${currency}`;
}

function formatDate(iso: string | null) {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('fr-FR', { year: 'numeric', month: 'long', day: 'numeric' });
}

export default function Invoice({ subscription, customer }: { subscription: SubscriptionDetail; customer: Customer }) {
    return (
        <div className="mx-auto max-w-2xl px-6 py-12">
            <Head title={`Facture #${subscription.id}`} />

            <div className="mb-6 flex items-center justify-between print:hidden">
                <Link href="/settings/subscription/history">← Retour à l'historique</Link>
                <Button variant="outline" size="sm" onClick={() => window.print()}>
                    Imprimer
                </Button>
            </div>

            <div className="border border-slate-300 p-8 print:border-0 print:p-0">
                <div className="mb-8 flex items-start justify-between border-b border-slate-300 pb-6">
                    <div>
                        <div className="font-mono text-lg font-bold text-slate-900">KongoInvestment</div>
                        <p className="text-caption">La plateforme d'investissement propulsée par l'IA, entre la RDC et le monde.</p>
                    </div>
                    <div className="text-right">
                        <div className="text-eyebrow">Facture</div>
                        <div className="font-mono text-sm text-slate-500">#{subscription.id}</div>
                    </div>
                </div>

                <div className="mb-8 grid grid-cols-2 gap-6">
                    <div>
                        <div className="mb-1 font-mono text-[11px] uppercase tracking-wide text-slate-500">Facturé à</div>
                        <div className="font-semibold text-slate-900">{customer.name}</div>
                        <div className="text-sm text-slate-600">{customer.email}</div>
                    </div>
                    <div className="text-right">
                        <div className="mb-1 font-mono text-[11px] uppercase tracking-wide text-slate-500">Statut</div>
                        <Badge className={statusClasses[subscription.status] ?? 'bg-slate-100 text-slate-500'}>{subscription.statusLabel}</Badge>
                    </div>
                </div>

                <table className="mb-8 w-full text-sm">
                    <thead>
                        <tr className="border-b border-slate-300 text-left font-mono text-[11px] uppercase tracking-wide text-slate-500">
                            <th className="pb-2 font-normal">Description</th>
                            <th className="pb-2 text-right font-normal">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr className="border-b border-slate-200">
                            <td className="py-3">
                                Abonnement {subscription.planName} — {subscription.billingPeriod === 'annual' ? 'facturation annuelle' : 'facturation mensuelle'}
                            </td>
                            <td className="py-3 text-right font-mono">{formatAmount(subscription.amountMinor, subscription.currency)}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td className="pt-3 text-right font-semibold">Total</td>
                            <td className="pt-3 text-right font-mono font-bold text-slate-900">
                                {formatAmount(subscription.amountMinor, subscription.currency)}
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <div className="grid grid-cols-2 gap-6 border-t border-slate-300 pt-6 text-sm">
                    <div>
                        <div className="mb-1 font-mono text-[11px] uppercase tracking-wide text-slate-500">Date de début</div>
                        <div className="text-slate-900">{formatDate(subscription.startsAt)}</div>
                    </div>
                    <div>
                        <div className="mb-1 font-mono text-[11px] uppercase tracking-wide text-slate-500">Moyen de paiement</div>
                        <div className="text-slate-900">
                            {subscription.paymentProvider ? (providerLabels[subscription.paymentProvider] ?? subscription.paymentProvider) : '—'}
                        </div>
                    </div>
                    {subscription.endsAt && (
                        <div>
                            <div className="mb-1 font-mono text-[11px] uppercase tracking-wide text-slate-500">Date de fin</div>
                            <div className="text-slate-900">{formatDate(subscription.endsAt)}</div>
                        </div>
                    )}
                    {subscription.canceledAt && (
                        <div>
                            <div className="mb-1 font-mono text-[11px] uppercase tracking-wide text-slate-500">Annulé le</div>
                            <div className="text-slate-900">{formatDate(subscription.canceledAt)}</div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
