import { Deferred } from '@inertiajs/react';
import { PricingGrid } from '@/components/PricingGrid';
import type { Plan } from '@/components/PricingGrid';
import { SeoHead } from '@/components/SeoHead';
import { PricingGridSkeleton } from '@/components/ui/page-skeletons';
import { PublicLayout } from '@/layouts/PublicLayout';

export default function Pricing({ plans }: { plans?: Plan[] }) {
    return (
        <PublicLayout>
            <SeoHead
                title="Tarifs"
                description="Les plans KongoInvest pour entrepreneurs, investisseurs et professionnels — un plan Free pour démarrer, sans engagement."
            />
            <div className="mx-auto max-w-6xl px-4 py-16 md:px-6">
                <p className="text-eyebrow">Tarifs</p>
                <h1 className="mt-2 mb-10 text-page-title-lg">
                    Un plan pour chaque étape.
                </h1>

                <Deferred data="plans" fallback={<PricingGridSkeleton />}>
                    <PricingGrid plans={plans ?? []} />
                </Deferred>
            </div>
        </PublicLayout>
    );
}
