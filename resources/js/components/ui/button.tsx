import type { ButtonHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export type ButtonVariant =
    | 'primary'
    | 'outline'
    | 'secondary'
    | 'cta'
    | 'destructive'
    | 'destructive-outline'
    | 'ghost'
    | 'text';
export type ButtonSize = 'default' | 'sm' | 'inline';

export const buttonVariantClasses: Record<ButtonVariant, string> = {
    primary:
        'border border-ink bg-ink text-paper capitalize tracking-[0.06em] hover:opacity-85',
    outline:
        'border border-rule-strong bg-transparent text-ink capitalize tracking-[0.06em] hover:border-ink',
    secondary:
        'border border-rule-strong bg-paper text-ink capitalize tracking-[0.06em] hover:border-ink',
    cta: 'border border-ink bg-ink text-paper capitalize tracking-[0.06em] hover:opacity-85',
    destructive:
        'border border-danger bg-danger text-danger-ink capitalize tracking-[0.06em] hover:opacity-85',
    'destructive-outline':
        'border border-danger bg-transparent text-danger capitalize tracking-[0.06em] hover:bg-danger/10',
    ghost: 'text-ink-muted hover:text-ink',
    // Same blue as Link's "text" variant, but a <button> — for JS actions
    // (e.g. "Mot de passe oublié ?") that aren't real navigation.
    text: 'text-action underline decoration-1 underline-offset-4 hover:decoration-2',
};

export const buttonSizeClasses: Record<ButtonSize, string> = {
    default: 'min-h-11 px-4 py-2.5 text-lg font-bold',
    sm: 'min-h-10 px-3 py-1.5 text-lg font-bold',
    // No padding — pairs with variant="text" for inline text-styled buttons.
    inline: 'text-lg font-bold',
};

export const buttonBaseClasses =
    'inline-flex cursor-pointer items-center justify-center gap-2 rounded-none font-mono transition-[background-color,border-color,color,opacity] duration-150 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action disabled:cursor-not-allowed disabled:opacity-50';

type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
    variant?: ButtonVariant;
    size?: ButtonSize;
};

export function Button({
    variant = 'primary',
    size = 'default',
    className,
    ...props
}: ButtonProps) {
    return (
        <button
            className={cn(
                buttonBaseClasses,
                buttonVariantClasses[variant],
                buttonSizeClasses[size],
                className,
            )}
            {...props}
        />
    );
}
