import { createContext } from 'react';

/**
 * The kind of protected action a visitor tried to take before being
 * routed into auth. Stored so the action can be resumed after
 * login/registration completes — it must never fire twice (spec §7).
 */
export type PendingIntent = {
    action:
        | 'create_project'
        | 'contact_investor'
        | 'save_project'
        | 'request_connection'
        | 'start_investment'
        | 'ai_feature'
        | 'start_deal_room';
    payload?: Record<string, unknown>;
} | null;

export type AuthModalName =
    | 'login'
    | 'register'
    | 'forgotPassword'
    | 'resetPassword'
    | 'verifyEmail'
    | 'confirmPassword'
    | 'twoFactorChallenge'
    | null;

export type AuthModalContextValue = {
    activeModal: AuthModalName;
    pendingIntent: PendingIntent;
    openLogin: (intent?: PendingIntent) => void;
    openRegister: (intent?: PendingIntent) => void;
    openForgotPassword: () => void;
    openResetPassword: (token: string, email: string) => void;
    openVerifyEmail: () => void;
    openConfirmPassword: (intent?: PendingIntent) => void;
    openTwoFactorChallenge: () => void;
    close: () => void;
    /** Called by whatever screen owns the action once it has resumed it. */
    consumeIntent: () => PendingIntent;
};

export const AuthModalContext = createContext<AuthModalContextValue | null>(null);
