import type { SelectHTMLAttributes } from 'react';
import { useField } from '@/components/ui/Field';
import { cn } from '@/lib/utils';

type SelectSize = 'default' | 'sm';

const sizeClasses: Record<SelectSize, string> = {
    default: 'min-h-11 px-3 py-2.5 text-2xl sm:text-xl',
    sm: 'min-h-10 px-2 py-1.5 text-2xl sm:text-xl',
};

type SelectProps = Omit<SelectHTMLAttributes<HTMLSelectElement>, 'size'> & {
    size?: SelectSize;
    error?: boolean;
};

export function Select({
    size = 'default',
    error,
    className,
    id,
    'aria-describedby': ariaDescribedBy,
    'aria-invalid': ariaInvalid,
    ...props
}: SelectProps) {
    const field = useField();
    const showError = error || field?.hasError;
    const describedBy =
        [showError ? field?.errorId : null, ariaDescribedBy]
            .filter(Boolean)
            .join(' ') || undefined;

    return (
        <select
            id={id ?? field?.controlId}
            aria-describedby={describedBy}
            aria-invalid={ariaInvalid ?? (showError || undefined)}
            className={cn(
                'w-full rounded-none border bg-paper text-ink focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-0 focus-visible:outline-action disabled:cursor-not-allowed disabled:bg-surface-2 disabled:text-ink-muted',
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
