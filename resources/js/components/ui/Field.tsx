import { createContext, useContext, useId, type ReactNode } from 'react';
import { cn } from '@/lib/utils';

type FieldContextValue = {
    controlId: string;
    errorId: string;
    hasError: boolean;
};

const FieldContext = createContext<FieldContextValue | null>(null);

export function useField() {
    return useContext(FieldContext);
}

/**
 * Groups a label, control, and error message so they share one id and
 * stay associated for screen readers. Renders a plain div so it can
 * replace an existing field wrapper without changing the layout.
 */
export function Field({
    id,
    error = false,
    className,
    children,
}: {
    id?: string;
    error?: boolean;
    className?: string;
    children: ReactNode;
}) {
    const generatedId = useId();
    const controlId = id ?? `field-${generatedId}`;

    return (
        <FieldContext.Provider
            value={{
                controlId,
                errorId: `${controlId}-error`,
                hasError: error,
            }}
        >
            <div className={cn(className)}>{children}</div>
        </FieldContext.Provider>
    );
}
