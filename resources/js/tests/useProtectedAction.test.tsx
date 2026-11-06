import { render, screen, fireEvent, act } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { AuthModalContext } from '@/components/Auth/AuthModalContext';
import type { AuthModalContextValue } from '@/components/Auth/AuthModalContext';
import { useProtectedAction } from '@/hooks/useProtectedAction';

let mockAuthUser: { id: number } | null = null;

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: { auth: { user: mockAuthUser } } }),
}));

function TestHarness({
    openRegisterSpy,
    run,
}: {
    openRegisterSpy: (intent: unknown) => void;
    run: () => void;
}) {
    const contextValue = {
        activeModal: null,
        pendingIntent: null,
        openLogin: vi.fn(),
        openRegister: openRegisterSpy,
        openForgotPassword: vi.fn(),
        openResetPassword: vi.fn(),
        openVerifyEmail: vi.fn(),
        openConfirmPassword: vi.fn(),
        openTwoFactorChallenge: vi.fn(),
        close: vi.fn(),
        consumeIntent: vi.fn(),
    } as unknown as AuthModalContextValue;

    return (
        <AuthModalContext.Provider value={contextValue}>
            <Inner run={run} />
        </AuthModalContext.Provider>
    );
}

function Inner({ run }: { run: () => void }) {
    const trigger = useProtectedAction({ action: 'contact_investor' }, run);

    return <button onClick={() => trigger()}>Contacter</button>;
}

describe('useProtectedAction (spec §7 + §136)', () => {
    it('opens the Register modal instead of running the action when the visitor is a guest', () => {
        mockAuthUser = null;
        const openRegisterSpy = vi.fn();
        const run = vi.fn();

        render(<TestHarness openRegisterSpy={openRegisterSpy} run={run} />);
        fireEvent.click(screen.getByText('Contacter'));

        expect(openRegisterSpy).toHaveBeenCalledWith(
            expect.objectContaining({ action: 'contact_investor' }),
        );
        expect(run).not.toHaveBeenCalled();
    });

    it('runs the action directly, without opening any modal, when already authenticated', () => {
        mockAuthUser = { id: 1 };
        const openRegisterSpy = vi.fn();
        const run = vi.fn();

        render(<TestHarness openRegisterSpy={openRegisterSpy} run={run} />);
        fireEvent.click(screen.getByText('Contacter'));

        expect(run).toHaveBeenCalledTimes(1);
        expect(openRegisterSpy).not.toHaveBeenCalled();
    });

    it('resumes the action exactly once via the resume-intent event, never twice', () => {
        mockAuthUser = { id: 1 };
        const run = vi.fn();

        render(<TestHarness openRegisterSpy={vi.fn()} run={run} />);

        act(() => {
            window.dispatchEvent(
                new CustomEvent('KongoInvest:resume-intent', {
                    detail: { action: 'contact_investor' },
                }),
            );
        });
        act(() => {
            window.dispatchEvent(
                new CustomEvent('KongoInvest:resume-intent', {
                    detail: { action: 'save_project' },
                }),
            ); // a different action — must be ignored
        });

        expect(run).toHaveBeenCalledTimes(1);
    });
});
