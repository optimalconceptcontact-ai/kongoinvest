import type { InputHTMLAttributes, ReactNode } from 'react';
import { useField } from '@/components/ui/Field';
import { cn } from '@/lib/utils';

type InputSize = 'default' | 'sm';

export type { InputSize };

const sizeClasses: Record<InputSize, string> = {
    default: 'min-h-11 px-3 py-2 text-2xl sm:text-xl',
    sm: 'min-h-10 px-2 py-1.5 text-2xl sm:text-xl',
};

const sizeHeight: Record<InputSize, string> = {
    default: 'min-h-11 py-2 text-2xl sm:text-xl',
    sm: 'min-h-10 py-1.5 text-2xl sm:text-xl',
};

const shellBorderColor = (showError: boolean | undefined) =>
    showError
        ? 'border-danger has-focus:border-danger'
        : 'border-rule-strong has-focus:border-action';

/**
 * Shared shell for inputs composed of several parts (icon, prefix token,
 * country select...). Keeps one set of border/focus classes consistent
 * with the plain <Input>.
 */
export function inputShellClasses(
    showError: boolean | undefined,
    size: InputSize,
) {
    return cn(
        'flex w-full items-center rounded-none border bg-paper text-ink',
        sizeHeight[size],
        shellBorderColor(showError),
        'has-disabled:bg-surface-2',
    );
}

const tokenClasses =
    'shrink-0 select-none font-mono text-xl leading-none text-ink-muted/70';

type InputProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'size'> & {
    size?: InputSize;
    error?: boolean;
    /** Decorative leading icon, rendered aria-hidden. */
    icon?: ReactNode;
    /** Static leading token, e.g. "https://". */
    prefix?: ReactNode;
    /** Static trailing token, e.g. "%" or a currency code. */
    suffix?: ReactNode;
};

export function Input({
    size = 'default',
    error,
    icon,
    prefix,
    suffix,
    className,
    id,
    'aria-describedby': ariaDescribedBy,
    'aria-invalid': ariaInvalid,
    ...props
}: InputProps) {
    const field = useField();
    const showError = error || field?.hasError;
    const describedBy =
        [showError ? field?.errorId : null, ariaDescribedBy]
            .filter(Boolean)
            .join(' ') || undefined;

    if (icon || prefix || suffix) {
        return (
            <span className={cn(inputShellClasses(showError, size), 'gap-2 px-3', className)}>
                {icon && (
                    <span aria-hidden className="shrink-0 text-ink-muted/70">
                        {icon}
                    </span>
                )}
                {prefix && <span aria-hidden className={tokenClasses}>{prefix}</span>}
                <input
                    id={id ?? field?.controlId}
                    aria-describedby={describedBy}
                    aria-invalid={ariaInvalid ?? (showError || undefined)}
                    className="w-full min-w-0 flex-1 self-stretch bg-transparent py-2 text-inherit placeholder:text-ink-muted/70 focus:outline-none disabled:cursor-not-allowed"
                    {...props}
                />
                {suffix && <span aria-hidden className={tokenClasses}>{suffix}</span>}
            </span>
        );
    }

    return (
        <input
            id={id ?? field?.controlId}
            aria-describedby={describedBy}
            aria-invalid={ariaInvalid ?? (showError || undefined)}
            className={cn(
                'w-full rounded-none border bg-paper text-ink placeholder:text-ink-muted/70 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-0 focus-visible:outline-action disabled:cursor-not-allowed disabled:bg-surface-2 disabled:text-ink-muted',
                sizeClasses[size],
                showError
                    ? 'border-danger focus:border-danger focus-visible:outline-danger'
                    : 'border-rule-strong focus:border-action',
                className,
            )}
            {...props}
        />
    );
}

export { sizeClasses };
