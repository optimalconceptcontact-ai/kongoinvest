import type { InputHTMLAttributes, ReactNode } from 'react';
import { cn } from '@/lib/utils';

type CheckboxProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'type' | 'size'> & {
    label: ReactNode;
    size?: 'default' | 'sm';
};

export function Checkbox({ label, size = 'default', className, ...props }: CheckboxProps) {
    return (
        <label className={cn('flex items-center gap-1.5 text-slate-700', size === 'sm' ? 'text-xs' : 'text-sm', className)}>
            <input type="checkbox" {...props} />
            {label}
        </label>
    );
}
