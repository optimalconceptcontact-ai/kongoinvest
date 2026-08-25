import type { RemixiconComponentType } from '@remixicon/react';
import { motion } from 'framer-motion';
import type { ReactNode } from 'react';
import { fadeUp } from '@/lib/animations';

/**
 * Spec §139: a reusable EmptyState/ErrorState pattern. Many pages built
 * across earlier phases (Entrepreneur/Investor match lists, connections,
 * FAQ search, project discovery) each hand-rolled their own
 * "border-dashed... Aucun X" div — functionally identical, never
 * unified. This is that unification point; existing pages weren't all
 * swept to use it (noted honestly in the Phase 14 README section), but
 * any new page should reach for this first.
 */
export function EmptyState({
    icon: Icon,
    title,
    description,
    action,
}: {
    icon?: RemixiconComponentType;
    title: string;
    description?: string;
    action?: ReactNode;
}) {
    return (
        <motion.div
            variants={fadeUp}
            initial="initial"
            animate="animate"
            className="flex flex-col items-center gap-3 border border-dashed border-slate-300 py-16 text-center"
        >
            {Icon && <Icon size={28} className="text-slate-400" />}
            <p className="text-sm font-medium text-slate-700">{title}</p>
            {description && (
                <p className="max-w-sm text-caption">{description}</p>
            )}
            {action}
        </motion.div>
    );
}
