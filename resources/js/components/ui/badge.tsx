import type { HTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

type BadgeVariant = 'filled' | 'outline';

const variantClasses: Record<BadgeVariant, string> = {
    filled: 'px-2 py-0.5 font-mono text-[11px] uppercase',
    outline: 'border border-slate-200 px-2 py-0.5 font-mono text-[10px] uppercase text-slate-500',
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
    return <span className={cn(variantClasses[variant], className)} {...props} />;
}
