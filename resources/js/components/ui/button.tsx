import type { ButtonHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export type ButtonVariant = 'primary' | 'outline' | 'secondary' | 'cta' | 'destructive' | 'destructive-outline' | 'ghost' | 'text';
export type ButtonSize = 'default' | 'sm' | 'inline';

export const buttonVariantClasses: Record<ButtonVariant, string> = {
    primary: 'bg-slate-900 text-white hover:bg-blue-700',
    outline: 'border border-slate-300 text-slate-900 hover:border-slate-900',
    secondary: 'bg-blue-600 text-white hover:bg-blue-700',
    cta: 'bg-orange-500 text-white hover:bg-orange-600',
    destructive: 'bg-orange-600 text-white hover:bg-orange-700',
    'destructive-outline': 'border border-orange-600 text-orange-600 hover:bg-orange-50',
    ghost: 'text-slate-500 hover:text-slate-900',
    // Same blue as Link's "text" variant, but a <button> — for JS actions
    // (e.g. "Mot de passe oublié ?") that aren't real navigation.
    text: 'text-blue-600 hover:text-blue-700',
};

export const buttonSizeClasses: Record<ButtonSize, string> = {
    default: 'px-4 py-2 text-base font-semibold',
    sm: 'px-3 py-1.5 text-sm font-semibold',
    // No padding — pairs with variant="text" for inline text-styled buttons.
    inline: 'text-sm font-medium',
};

type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
    variant?: ButtonVariant;
    size?: ButtonSize;
};

export function Button({ variant = 'primary', size = 'default', className, ...props }: ButtonProps) {
    return (
        <button
            className={cn(buttonVariantClasses[variant], buttonSizeClasses[size], 'disabled:opacity-50', className)}
            {...props}
        />
    );
}
