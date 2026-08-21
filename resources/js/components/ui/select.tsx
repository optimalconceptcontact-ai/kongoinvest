import type { SelectHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

type SelectSize = 'default' | 'sm';

const sizeClasses: Record<SelectSize, string> = {
    default: 'px-3 py-2 text-sm',
    sm: 'px-2 py-1.5 text-xs',
};

type SelectProps = Omit<SelectHTMLAttributes<HTMLSelectElement>, 'size'> & {
    size?: SelectSize;
    error?: boolean;
};

export function Select({ size = 'default', error, className, ...props }: SelectProps) {
    return (
        <select
            className={cn(
                'w-full border focus:outline-none',
                sizeClasses[size],
                error ? 'border-orange-600 focus:border-orange-600' : 'border-slate-300 focus:border-blue-600',
                className,
            )}
            {...props}
        />
    );
}
