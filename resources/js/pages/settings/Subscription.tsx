import { Head, router } from '@inertiajs/react';
import { RiCheckLine } from '@remixicon/react';

type Plan = {
    id: number;
    key: string;
    name: string;
    roleScope: string | null;
    priceMinor: number;
    currency: string;
    billingPeriod: string;
    limits: Record<string, number>;
    features: string[];
    availableToViewer: boolean;
    isCurrent: boolean;
};

const featureLabels: Record<string, string> = {
    advanced_matching: 'Matching avancé',
    ai_coach: "Coach IA d'amélioration",
    ai_investment_advisor: "Conseiller IA d'investissement",
    contact_info_visible: 'Coordonnées visibles',
    negotiation_tools: 'Outils de négociation',
    deal_room_access: 'Accès Deal Room',
    escrow_access: 'Accès Escrow',
    kyc_access: 'Accès KYC',
    contracts_access: 'Accès contrats',
    portfolio_tracking: 'Suivi de portefeuille',
};

const limitLabels: Record<string, string> = {
    max_active_projects: 'projet(s) actif(s)',
    ai_monthly_token_quota: "jetons IA / mois",
};

function formatPrice(priceMinor: number, currency: string) {
    if (priceMinor === 0) return 'Gratuit';
    return `${(priceMinor / 100).toLocaleString('fr-FR')} ${currency}/mois`;
}

export default function Subscription({ plans, currentPlanKey }: { plans: Plan[]; currentPlanKey: string }) {
    function switchTo(key: string) {
        if (key === currentPlanKey) return;
        if (!confirm(`Passer au plan ${key} ?`)) return;
        router.put('/settings/subscription', { plan: key });
    }

    return (
        <>
            <Head title="Abonnement" />
            <div className="mx-auto max-w-5xl px-6 py-12">
                <p className="text-eyebrow">Paramètres</p>
                <h1 className="mt-2 mb-2 text-page-title">Abonnement</h1>
                <p className="mb-10 text-body">
                    Le paiement réel (Stripe, PayPal, Mobile Money) sera activé en Phase 10 — les changements de
                    plan ci-dessous sont immédiats et gratuits pour l'instant.
                </p>

                <div className="grid grid-cols-1 gap-0 border border-slate-200 md:grid-cols-4">
                    {plans.map((plan) => (
                        <div
                            key={plan.id}
                            className={`flex flex-col border-slate-200 p-6 md:border-r last:border-r-0 ${
                                plan.isCurrent ? 'bg-slate-900 text-white' : ''
                            }`}
                        >
                            <div className={`font-mono text-[11px] uppercase tracking-wide ${plan.isCurrent ? 'text-slate-300' : 'text-slate-500'}`}>
                                {plan.isCurrent ? 'Plan actuel' : plan.roleScope ? `Pour les ${plan.roleScope}s` : 'Tous rôles'}
                            </div>
                            <div className="mt-2 mb-1 text-lg font-bold">{plan.name}</div>
                            <div className={`mb-5 font-mono text-xl font-bold ${plan.isCurrent ? '' : 'text-slate-900'}`}>
                                {formatPrice(plan.priceMinor, plan.currency)}
                            </div>

                            <ul className="mb-6 flex flex-1 flex-col gap-2 text-xs">
                                {Object.entries(plan.limits).map(([key, value]) => (
                                    <li key={key} className="flex items-center gap-2">
                                        <RiCheckLine size={14} className={plan.isCurrent ? 'text-orange-400' : 'text-blue-600'} />
                                        {value.toLocaleString('fr-FR')} {limitLabels[key] ?? key}
                                    </li>
                                ))}
                                {plan.features.map((f) => (
                                    <li key={f} className="flex items-center gap-2">
                                        <RiCheckLine size={14} className={plan.isCurrent ? 'text-orange-400' : 'text-blue-600'} />
                                        {featureLabels[f] ?? f}
                                    </li>
                                ))}
                            </ul>

                            <button
                                disabled={!plan.availableToViewer || plan.isCurrent}
                                onClick={() => switchTo(plan.key)}
                                className={`px-4 py-2.5 text-xs font-semibold ${
                                    plan.isCurrent
                                        ? 'cursor-default bg-white/10 text-slate-300'
                                        : plan.availableToViewer
                                          ? 'bg-orange-500 text-white hover:bg-orange-600'
                                          : 'cursor-not-allowed border border-slate-200 text-slate-300'
                                }`}
                            >
                                {plan.isCurrent ? 'Plan actuel' : plan.availableToViewer ? 'Choisir ce plan' : 'Non disponible pour votre rôle'}
                            </button>
                        </div>
                    ))}
                </div>
            </div>
        </>
    );
}
