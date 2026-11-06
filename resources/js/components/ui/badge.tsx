import type { HTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

type BadgeVariant = 'filled' | 'outline';

const variantClasses: Record<BadgeVariant, string> = {
    filled: 'inline-flex items-center px-2 py-1 font-mono text-lg tracking-[0.06em] uppercase',
    outline:
        'inline-flex items-center border border-rule-strong px-2 py-1 font-mono text-lg tracking-[0.06em] uppercase text-ink-muted',
};

type BadgeProps = HTMLAttributes<HTMLSpanElement> & {
    variant?: BadgeVariant;
};

/**
 * Shape only — every page defines its own status → color map (KYC,
 * disputes, projects, etc. don't share a palette), so "filled" expects
 * the caller to pass bg-* / text-* via className; only "outline" (the
 * neutral variant) has a fixed color.
 */
export function Badge({ variant = 'filled', className, ...props }: BadgeProps) {
    return (
        <span className={cn(variantClasses[variant], className)} {...props} />
    );
}
