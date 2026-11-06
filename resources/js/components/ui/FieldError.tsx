import type { ReactNode } from 'react';
import { useField } from '@/components/ui/Field';

export function FieldError({
    children,
    id,
    className,
}: {
    children?: ReactNode;
    id?: string;
    className?: string;
}) {
    const field = useField();

    if (!children) return null;

    return (
        <p
            id={id ?? field?.errorId}
            role="alert"
            className={className ?? 'text-form-error'}
        >
            {children}
        </p>
    );
}
