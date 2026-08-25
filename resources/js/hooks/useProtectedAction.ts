import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { useAuthModal } from '@/hooks/useAuthModal';
import type { PendingIntent } from '@/components/Auth/AuthModalContext';

type SharedAuth = { auth: { user: { id: number } | null } };

/**
 * Wrap any button/action that requires an authenticated user — "Contact
 * Investor", "Save Project", "Request Connection", "Start Investment",
 * an AI feature, a Deal Room, etc. (spec §7).
 *
 * If the visitor is a guest, this opens the Register modal with the
 * intent attached instead of running `run`. Once auth completes, the
 * AuthModalProvider dispatches `kongoinvestment:resume-intent` and whichever
 * component owns that action is responsible for actually resuming it —
 * this hook only listens for its *own* intent so the action can never
 * fire twice.
 */
export function useProtectedAction<T extends PendingIntent>(
    intent: NonNullable<T>,
    run: (payload?: Record<string, unknown>) => void,
) {
    const { props } = usePage<SharedAuth>();
    const { openRegister } = useAuthModal();
    const isAuthenticated = Boolean(props.auth.user);

    useEffect(() => {
        function onResume(e: Event) {
            const detail = (e as CustomEvent<PendingIntent>).detail;
            if (detail?.action === intent.action) run(detail.payload);
        }

        window.addEventListener('kongoinvestment:resume-intent', onResume);
        return () => window.removeEventListener('kongoinvestment:resume-intent', onResume);
    }, [intent.action, run]);

    return function trigger(payload?: Record<string, unknown>) {
        if (!isAuthenticated) {
            openRegister({ ...intent, payload });
            return;
        }
        run(payload);
    };
}
