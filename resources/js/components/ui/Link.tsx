import {
    Link as InertiaLink,
    type InertiaLinkProps,
} from '@inertiajs/react';
import { cn } from '@/lib/utils';
import {
    buttonBaseClasses,
    buttonSizeClasses,
    buttonVariantClasses,
    type ButtonSize,
    type ButtonVariant,
} from './button';

const textLinkClasses =
    'inline-flex min-h-11 items-center font-mono text-base font-medium text-action underline decoration-1 underline-offset-4 hover:decoration-2 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action';

type LinkProps = Omit<InertiaLinkProps, 'size'> & {
    variant?: ButtonVariant | 'text';
    size?: ButtonSize;
};

/**
 * Every "link" in the app is either a plain inline/nav link (mono, blue,
 * spec's default) or a Link doing double duty as a button (spec §81's
 * CTAs) — same Button variants apply, just rendered as an <a> via Inertia.
 */
export function Link({
    variant = 'text',
    size = 'default',
    className,
    ...props
}: LinkProps) {
    const classes =
        variant === 'text'
            ? cn(textLinkClasses, className)
            : cn(
                  buttonBaseClasses,
                  buttonVariantClasses[variant],
                  buttonSizeClasses[size],
                  className,
              );

    return <InertiaLink className={classes} {...props} />;
}
