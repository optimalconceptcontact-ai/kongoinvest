import { motion } from 'framer-motion';
import type { ReactNode } from 'react';
import { fadeUp } from '@/lib/animations';

/**
 * Soft, fast enter transition applied to page content. Inertia remounts
 * the page component on every visit, so a plain initial/animate is all
 * that's needed — no AnimatePresence exit choreography required.
 */
export function PageTransition({ children }: { children: ReactNode }) {
    return (
        <motion.div variants={fadeUp} initial="initial" animate="animate">
            {children}
        </motion.div>
    );
}
