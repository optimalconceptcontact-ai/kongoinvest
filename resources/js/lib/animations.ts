import type { Transition, Variants } from 'framer-motion';

/**
 * Shared framer-motion presets — the house style is "doux mais rapide":
 * short distances (6-12px), quick durations (0.18-0.28s), easeOut curves
 * and soft springs without bounce. Always animate opacity/transform only
 * (GPU-composited) and let `MotionConfig reducedMotion="user"` in
 * root-layout disable everything for users who prefer reduced motion.
 */

export const easeSoft: [number, number, number, number] = [
    0.25, 0.46, 0.45, 0.94,
];

export const transitionFast: Transition = {
    duration: 0.2,
    ease: easeSoft,
};

export const transitionSpring: Transition = {
    type: 'spring',
    stiffness: 320,
    damping: 30,
    mass: 0.9,
};

export const fadeIn: Variants = {
    initial: { opacity: 0 },
    animate: { opacity: 1, transition: transitionFast },
    exit: { opacity: 0, transition: { duration: 0.15, ease: 'easeOut' } },
};

export const fadeUp: Variants = {
    initial: { opacity: 0, y: 8 },
    animate: { opacity: 1, y: 0, transition: transitionFast },
    exit: {
        opacity: 0,
        y: -4,
        transition: { duration: 0.15, ease: 'easeOut' },
    },
};

export const scaleIn: Variants = {
    initial: { opacity: 0, scale: 0.97, y: 4 },
    animate: { opacity: 1, scale: 1, y: 0, transition: transitionSpring },
    exit: {
        opacity: 0,
        scale: 0.98,
        transition: { duration: 0.12, ease: 'easeOut' },
    },
};

/** Panel sliding in from the right edge (drawers, side sheets). */
export const slideInRight: Variants = {
    initial: { x: '100%' },
    animate: { x: 0, transition: transitionSpring },
    exit: { x: '100%', transition: { duration: 0.2, ease: 'easeIn' } },
};

/** Parent container that staggers its `staggerItem` children. */
export const staggerContainer = (
    stagger = 0.05,
    delayChildren = 0,
): Variants => ({
    initial: {},
    animate: {
        transition: { staggerChildren: stagger, delayChildren },
    },
});

/** Child item of a `staggerContainer`. */
export const staggerItem: Variants = {
    initial: { opacity: 0, y: 10 },
    animate: { opacity: 1, y: 0, transition: transitionFast },
};

/** Subtle card hover/press feedback (lift + release). */
export const hoverLift = {
    rest: { y: 0 },
    hover: { y: -2, transition: transitionFast },
    tap: { y: 0, transition: { duration: 0.1 } },
};
