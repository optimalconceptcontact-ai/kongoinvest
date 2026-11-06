import type { LabelHTMLAttributes } from 'react';
import { useField } from '@/components/ui/Field';
import { cn } from '@/lib/utils';

type LabelProps = LabelHTMLAttributes<HTMLLabelElement>;

export function Label({ className, htmlFor, ...props }: LabelProps) {
    const field = useField();

    return (
        <label
            htmlFor={htmlFor ?? field?.controlId}
            className={cn('text-form-label cursor-pointer', className)}
            {...props}
        />
    );
}
