import { router, usePage } from '@inertiajs/react';
import { RiCheckLine } from '@remixicon/react';
import { Button } from '@/components/ui/button';
import { useAuthModal } from '@/hooks/useAuthModal';
import { useConfirm } from '@/hooks/useConfirm';

export type Plan = {
    id: number;
    key: string;
    name: string;
    roleScope: string | null;
    priceMinor: number;
    annualPriceMinor: number | null;
    currency: string;
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
    ai_monthly_token_quota: 'jetons IA / mois',
};

function formatPrice(priceMinor: number, currency: string) {
    if (priceMinor === 0) {
        return 'Gratuit';
    }

    return `${(priceMinor / 100).toLocaleString('fr-FR')} ${currency}/mois`;
}

function formatAnnualNote(annualPriceMinor: number | null, currency: string) {
    if (!annualPriceMinor) {
        return null;
    }

    return `ou ${(annualPriceMinor / 100).toLocaleString('fr-FR')} ${currency}/an (2 mois offerts)`;
}

/**
 * Shared pricing grid (Public/Pricing page + Welcome section): guests see
 * every plan with "Commencer", connected users only see the plans open to
 * their role with "En cours" on their current plan and "Souscrire"
 * elsewhere. Same state machine as Settings/Subscription — the current
 * plan is disabled, Free downgrades instantly (running paid subscription
 * ends immediately with no refund), and paid targets go through checkout.
 */
export function PricingGrid({ plans }: { plans: Plan[] }) {
    const { openRegister } = useAuthModal();
    const { props } = usePage<{ auth: { user: { id: number } | null } }>();
    const { confirm, ConfirmDialog } = useConfirm();
    const current = plans.find((plan) => plan.isCurrent);

    // Guests see every plan; connected users only see the plans open to
    // their role — no dead "unavailable" cards.
    const visiblePlans = plans.filter(
        (plan) => props.auth.user === null || plan.availableToViewer,
    );

    // The grid follows the visible count so connected users (3 plans, or
    // 2 for admins) get full-width columns instead of an empty 4th cell.
    const gridColsClass =
        {
            4: 'md:grid-cols-4',
            3: 'md:grid-cols-3',
            2: 'md:grid-cols-2',
        }[visiblePlans.length] ?? '';

    async function subscribe(plan: Plan) {
        if (plan.isCurrent) {
            return;
        }

        if (plan.priceMinor === 0) {
            if (
                !(await confirm(
                    `Revenir au plan Free ? Cela annule immédiatement votre abonnement ${current?.name ?? ''} sans remboursement, même avant son échéance.`,
                ))
            ) {
                return;
            }
        } else if (current && current.priceMinor > 0) {
            if (
                !(await confirm(
                    `Passer au plan ${plan.name} ? Cela annule votre abonnement ${current.name} sans remboursement. Vous serez redirigé vers le paiement pour activer le nouveau plan.`,
                ))
            ) {
                return;
            }
        } else if (
            !(await confirm(
                `Passer au plan ${plan.name} ? Vous serez redirigé vers le paiement.`,
            ))
        ) {
            return;
        }

        router.put('/settings/subscription', { plan: plan.key });
    }

    return (
        <>
            <div
                className={`grid grid-cols-1 border border-rule-strong ${gridColsClass}`}
            >
                {visiblePlans.map((plan) => (
                    <div
                        key={plan.id}
                        className={`flex w-full flex-col border-b border-rule p-6 last:border-b-0 md:border-r md:border-b-0 md:last:border-r-0 ${
                            plan.isCurrent ? 'bg-surface-2' : 'bg-paper'
                        }`}
                    >
                        <div className="flex min-h-5 items-center justify-between gap-2">
                            <div className="font-mono text-base tracking-[0.12em] text-ink-muted uppercase">
                                {plan.roleScope
                                    ? `Pour les ${plan.roleScope}s`
                                    : 'Tous rôles'}
                            </div>
                            {plan.isCurrent && (
                                <span className="px-2 py-1 flex items-center bg-signal gap-1.5 font-mono text-lg text-ink">
                                    Actuel
                                </span>
                            )}
                        </div>
                        <div className="mt-3 text-item-title">
                            {plan.name}
                        </div>
                        <div className="mt-1 font-mono text-4xl font-bold tracking-[-0.03em] text-ink">
                            {formatPrice(plan.priceMinor, plan.currency)}
                        </div>
                        <div className="">
                            {formatAnnualNote(
                                plan.annualPriceMinor,
                                plan.currency,
                            )}
                        </div>

                        <ul className="mb-8 flex flex-1 flex-col gap-2 text-base text-ink">
                            {Object.entries(plan.limits).map(([key, value]) => (
                                <li
                                    key={key}
                                    className="flex items-center gap-2"
                                >
                                    <RiCheckLine
                                        size={15}
                                        className="flex-shrink-0 text-ink"
                                    />
                                    <span className="font-mono text-lg">
                                        {value.toLocaleString('fr-FR')}{' '}
                                        {limitLabels[key] ?? key}
                                    </span>
                                </li>
                            ))}
                            {plan.features.map((f) => (
                                <li key={f} className="flex items-center gap-2">
                                    <RiCheckLine
                                        size={15}
                                        className="flex-shrink-0 text-ink"
                                    />
                                    {featureLabels[f] ?? f}
                                </li>
                            ))}
                        </ul>

                        {props.auth.user === null ? (
                            <Button
                                type="button"
                                onClick={() => openRegister()}
                                variant="primary"
                                className="w-full px-4 py-2.5"
                            >
                                Commencer
                            </Button>
                        ) : plan.isCurrent ? (
                            <>
                            
                            </>
                        ) : (
                            <Button
                                type="button"
                                onClick={() => subscribe(plan)}
                                variant="primary"
                                className="w-full px-4 py-2.5"
                            >
                                Souscrire
                            </Button>
                        )}
                    </div>
                ))}
            </div>
            {ConfirmDialog}
        </>
    );
}
