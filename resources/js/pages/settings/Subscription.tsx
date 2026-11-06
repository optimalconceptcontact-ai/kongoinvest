import { Head } from '@inertiajs/react';
import { PricingGrid } from '@/components/PricingGrid';
import type { Plan } from '@/components/PricingGrid';
import { BackLink } from '@/components/ui/BackLink';
import { Link } from '@/components/ui/Link';

export default function Subscription({ plans }: { plans: Plan[] }) {
    return (
        <>
            <Head title="Abonnement" />
            <div className="mx-auto max-w-5xl px-6 py-12">
                <BackLink href="/dashboard" />
                <p className="text-eyebrow">Paramètres</p>
                <div className="mt-2 mb-2 flex flex-wrap items-end justify-between gap-3">
                    <h1 className="text-page-title">Abonnement</h1>
                    <Link href="/settings/subscription/history">
                        Historique des abonnements →
                    </Link>
                </div>
                <p className="mb-10 text-body">
                    Le passage à un plan payant vous redirige vers une page de
                    paiement (Stripe, PayPal, Mobile Money). Le plan Free reste
                    immédiat et gratuit. Revenir au plan Free annule
                    l'abonnement en cours immédiatement, sans remboursement.
                </p>

                <PricingGrid plans={plans} />
            </div>
        </>
    );
}
