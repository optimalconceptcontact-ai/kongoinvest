import { SeoHead } from '@/components/SeoHead';
import { RiCheckLine } from '@remixicon/react';
import { Button } from '@/components/Ui/Button';
import { useAuthModal } from '@/hooks/useAuthModal';
import { PublicLayout } from '@/layouts/PublicLayout';

type Plan = {
    id: number;
    key: string;
    name: string;
    roleScope: string | null;
    priceMinor: number;
    currency: string;
    limits: Record<string, number>;
    features: string[];
    availableToViewer: boolean;
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

function formatPrice(priceMinor: number, currency: string, key: string) {
    if (key === 'professional') return 'Sur devis';
    if (priceMinor === 0) return 'Gratuit';
    return `${(priceMinor / 100).toLocaleString('fr-FR')} ${currency}/mois`;
}

export default function Pricing({ plans }: { plans: Plan[] }) {
    const { openRegister } = useAuthModal();

    return (
        <PublicLayout>
            <SeoHead
                title="Tarifs"
                description="Les plans Kanda pour entrepreneurs, investisseurs et professionnels — un plan Free pour démarrer, sans engagement."
            />
            <div className="mx-auto max-w-6xl px-6 py-16">
                <p className="text-eyebrow">Tarifs</p>
                <h1 className="mt-2 mb-10 text-page-title-lg">Un plan pour chaque étape.</h1>

                <div className="grid grid-cols-1 border border-slate-200 md:grid-cols-4">
                    {plans.map((plan) => (
                        <div key={plan.id} className="flex flex-col border-slate-200 p-6 md:border-r last:border-r-0">
                            <div className="font-mono text-[11px] uppercase tracking-wide text-slate-500">
                                {plan.roleScope ? `Pour les ${plan.roleScope}s` : 'Tous rôles'}
                            </div>
                            <div className="mt-2 mb-1 text-item-title-lg">{plan.name}</div>
                            <div className="mb-6 font-mono text-xl font-bold text-slate-900">
                                {formatPrice(plan.priceMinor, plan.currency, plan.key)}
                            </div>

                            <ul className="mb-8 flex flex-1 flex-col gap-2 text-xs text-slate-600">
                                {Object.entries(plan.limits).map(([key, value]) => (
                                    <li key={key} className="flex items-center gap-2">
                                        <RiCheckLine size={14} className="text-blue-600" />
                                        {value.toLocaleString('fr-FR')} {limitLabels[key] ?? key}
                                    </li>
                                ))}
                                {plan.features.map((f) => (
                                    <li key={f} className="flex items-center gap-2">
                                        <RiCheckLine size={14} className="text-blue-600" />
                                        {featureLabels[f] ?? f}
                                    </li>
                                ))}
                            </ul>

                            <Button
                                type="button"
                                onClick={() => openRegister()}
                                variant="outline"
                                className="border-slate-900 px-4 py-2.5 text-xs hover:bg-slate-900 hover:text-white"
                            >
                                Commencer
                            </Button>
                        </div>
                    ))}
                </div>
            </div>
        </PublicLayout>
    );
}
