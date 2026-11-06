import { usePage } from '@inertiajs/react';
import { type ReactNode, useCallback, useEffect, useState } from 'react';
import {
    AuthModalContext,
    type AuthModalName,
    type PendingIntent,
} from '@/components/Auth/AuthModalContext';
import { LoginModal } from '@/components/Auth/LoginModal';
import { RegisterModal } from '@/components/Auth/RegisterModal';
import { ForgotPasswordModal } from '@/components/Auth/ForgotPasswordModal';
import { ResetPasswordModal } from '@/components/Auth/ResetPasswordModal';
import { VerifyEmailModal } from '@/components/Auth/VerifyEmailModal';
import { ConfirmPasswordModal } from '@/components/Auth/ConfirmPasswordModal';
import { TwoFactorChallengeModal } from '@/components/Auth/TwoFactorChallengeModal';

type SharedProps = {
    // Set server-side (see HandleInertiaRequests) when a guest is redirected
    // back after hitting a protected action — lets a full page load restore
    // the modal + intent, not just client-side navigations.
    authModal?: { modal: AuthModalName; intent: PendingIntent } | null;
};

export function AuthModalProvider({ children }: { children: ReactNode }) {
    const page = usePage<SharedProps>();
    const [activeModal, setActiveModal] = useState<AuthModalName>(null);
    const [pendingIntent, setPendingIntent] = useState<PendingIntent>(null);
    const [resetToken, setResetToken] = useState<{
        token: string;
        email: string;
    } | null>(null);

    // Resume state from a server-driven redirect (spec §7).
    useEffect(() => {
        if (page.props.authModal) {
            setActiveModal(page.props.authModal.modal);
            setPendingIntent(page.props.authModal.intent ?? null);
        }
    }, [page.props.authModal]);

    const open = useCallback((modal: AuthModalName, intent?: PendingIntent) => {
        setPendingIntent(intent ?? null);
        setActiveModal(modal);
    }, []);

    const close = useCallback(() => setActiveModal(null), []);

    const consumeIntent = useCallback(() => {
        const intent = pendingIntent;
        setPendingIntent(null);
        return intent;
    }, [pendingIntent]);

    return (
        <AuthModalContext.Provider
            value={{
                activeModal,
                pendingIntent,
                openLogin: (intent) => open('login', intent),
                openRegister: (intent) => open('register', intent),
                openForgotPassword: () => open('forgotPassword'),
                openResetPassword: (token, email) => {
                    setResetToken({ token, email });
                    open('resetPassword');
                },
                openVerifyEmail: () => open('verifyEmail'),
                openConfirmPassword: (intent) =>
                    open('confirmPassword', intent),
                openTwoFactorChallenge: () => open('twoFactorChallenge'),
                close,
                consumeIntent,
            }}
        >
            {children}

            {/* Auth is always presented as a modal over the current page —
                never a standalone route (spec §6). Each modal reads
                `pendingIntent` on success and resumes the original action
                exactly once, then clears it via consumeIntent(). */}
            <LoginModal open={activeModal === 'login'} onClose={close} />
            <RegisterModal open={activeModal === 'register'} onClose={close} />
            <ForgotPasswordModal
                open={activeModal === 'forgotPassword'}
                onClose={close}
            />
            <ResetPasswordModal
                open={activeModal === 'resetPassword'}
                onClose={close}
                token={resetToken?.token}
                email={resetToken?.email}
            />
            <VerifyEmailModal
                open={activeModal === 'verifyEmail'}
                onClose={close}
            />
            <ConfirmPasswordModal
                open={activeModal === 'confirmPassword'}
                onClose={close}
            />
            <TwoFactorChallengeModal
                open={activeModal === 'twoFactorChallenge'}
                onClose={close}
            />
        </AuthModalContext.Provider>
    );
}
