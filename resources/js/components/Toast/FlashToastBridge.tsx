import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { useToast } from './ToastProvider';

type SharedFlash = {
    flash: { success?: string | null; error?: string | null };
};

/**
 * Every controller in this app already flashes `success`/`error` to the
 * session (back()->with('success', ...) etc, used since Phase 1) — that
 * data has been reaching the frontend the whole time via
 * HandleInertiaRequests' shared `flash` prop, but nothing ever turned it
 * into an actual Toast (spec §102). Individual pages grew their own
 * inline flash banners instead. This bridge is mounted once, globally,
 * so every existing flash message becomes a real toast retroactively —
 * no controller needs to change.
 */
export function FlashToastBridge() {
    const { props } = usePage<SharedFlash>();
    const toast = useToast();
    const lastSeen = useRef<{ success?: string | null; error?: string | null }>(
        {},
    );

    useEffect(() => {
        if (
            props.flash?.success &&
            props.flash.success !== lastSeen.current.success
        ) {
            toast.success(props.flash.success);
        }
        if (
            props.flash?.error &&
            props.flash.error !== lastSeen.current.error
        ) {
            toast.error(props.flash.error);
        }
        lastSeen.current = {
            success: props.flash?.success,
            error: props.flash?.error,
        };
    }, [props.flash?.success, props.flash?.error, toast]);

    return null;
}
