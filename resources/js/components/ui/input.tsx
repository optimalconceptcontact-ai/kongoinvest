import type { InputHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

type InputSize = 'default' | 'sm';

const sizeClasses: Record<InputSize, string> = {
    default: 'px-3 py-2 text-sm',
    sm: 'px-2 py-1.5 text-sm',
};

type InputProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'size'> & {
    size?: InputSize;
    error?: boolean;
};

export function Input({ size = 'default', error, className, ...props }: InputProps) {
    return (
        <input
            className={cn(
                'w-full border focus:outline-none',
                sizeClasses[size],
                error ? 'border-red-600 focus:border-red-600' : 'border-slate-400 focus:border-blue-600',
                className,
            )}
            {...props}
        />
    );
}
