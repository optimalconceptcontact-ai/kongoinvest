import type { InputHTMLAttributes, ReactNode } from 'react';
import { useField } from '@/components/ui/Field';
import { cn } from '@/lib/utils';

type CheckboxProps = Omit<
    InputHTMLAttributes<HTMLInputElement>,
    'type' | 'size'
> & {
    label: ReactNode;
    size?: 'default' | 'sm';
};

export function Checkbox({
    label,
    size = 'default',
    className,
    id,
    ...props
}: CheckboxProps) {
    const field = useField();

    return (
        <label
            className={cn(
                'flex cursor-pointer items-center gap-2.5 text-ink',
                size === 'sm'
                    ? 'min-h-9 text-lg'
                    : 'min-h-11 py-2 text-xl',
                className,
            )}
        >
            <input
                type="checkbox"
                id={id ?? field?.controlId}
                className="h-4 w-4 shrink-0 rounded-none border-rule-strong accent-action"
                {...props}
            />
            <span>{label}</span>
        </label>
    );
}
