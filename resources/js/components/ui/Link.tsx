import { Link as InertiaLink, type InertiaLinkProps } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { buttonSizeClasses, buttonVariantClasses, type ButtonSize, type ButtonVariant } from './Button';

const textLinkClasses = 'font-mono text-xs text-blue-600 hover:text-blue-700';

type LinkProps = Omit<InertiaLinkProps, 'size'> & {
    variant?: ButtonVariant | 'text';
    size?: ButtonSize;
};

/**
 * Every "link" in the app is either a plain inline/nav link (mono, blue,
 * spec's default) or a Link doing double duty as a button (spec §81's
 * CTAs) — same Button variants apply, just rendered as an <a> via Inertia.
 */
export function Link({ variant = 'text', size = 'default', className, ...props }: LinkProps) {
    const classes =
        variant === 'text'
            ? cn(textLinkClasses, className)
            : cn(buttonVariantClasses[variant], buttonSizeClasses[size], className);

    return <InertiaLink className={classes} {...props} />;
}
