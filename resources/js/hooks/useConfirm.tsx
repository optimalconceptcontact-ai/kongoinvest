import { useCallback, useRef, useState } from 'react';
import { ConfirmModal } from '@/components/ConfirmModal';

type ConfirmOptions = {
    title?: string;
    description: string;
    confirmLabel?: string;
    danger?: boolean;
};

/**
 * Drop-in async replacement for the native `confirm('...')` dialog —
 * `if (await confirm('Archiver ce projet ?')) { ... }` reads the same as
 * the native call but renders the app's own styled Modal instead (spec
 * §101 — native confirm() can't be styled or show a processing state).
 * Pass a string for a plain message, or an options object for a title,
 * a custom confirm label, and `danger` styling on destructive actions.
 */
export function useConfirm() {
    const [options, setOptions] = useState<ConfirmOptions | null>(null);
    const resolver = useRef<((value: boolean) => void) | undefined>(undefined);

    const confirm = useCallback((opts: ConfirmOptions | string) => {
        setOptions(typeof opts === 'string' ? { description: opts } : opts);

        return new Promise<boolean>((resolve) => {
            resolver.current = resolve;
        });
    }, []);

    function handleClose() {
        resolver.current?.(false);
        setOptions(null);
    }

    function handleConfirm() {
        resolver.current?.(true);
        setOptions(null);
    }

    const ConfirmDialog = (
        <ConfirmModal
            open={options !== null}
            onClose={handleClose}
            onConfirm={handleConfirm}
            title={options?.title ?? 'Confirmer'}
            description={options?.description ?? ''}
            confirmLabel={options?.confirmLabel}
            danger={options?.danger}
        />
    );

    return { confirm, ConfirmDialog };
}
