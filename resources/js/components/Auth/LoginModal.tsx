import { useForm } from '@inertiajs/react';
import { RiLockPasswordLine, RiMailLine } from '@remixicon/react';
import type { FormEvent } from 'react';
import { Modal } from '@/components/Modal';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/Field';
import { FieldError } from '@/components/ui/FieldError';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/Label';
import { useAuthModal } from '@/hooks/useAuthModal';

export function LoginModal({
    open,
    onClose,
}: {
    open: boolean;
    onClose: () => void;
}) {
    const { openRegister, openForgotPassword, consumeIntent } = useAuthModal();
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    function submit(e: FormEvent) {
        e.preventDefault();

        // POST /login is registered by Fortify. Once `php artisan
        // wayfinder:generate` has run, replace this string with the
        // generated action import — never hand-roll the URL elsewhere.
        post('/login', {
            onSuccess: () => {
                reset('password');
                onClose();
                // The action that triggered auth (e.g. "contact investor")
                // resumes exactly once here, never on the login attempt
                // itself, so it can't double-fire (spec §7).
                const intent = consumeIntent();

                if (intent) {
                    window.dispatchEvent(
                        new CustomEvent('KongoInvest:resume-intent', {
                            detail: intent,
                        }),
                    );
                }
            },
        });
    }

    return (
        <Modal open={open} onClose={onClose} title="Se connecter" maxWidth="md">
            <form onSubmit={submit} className="flex flex-col gap-4">
                <Field error={!!errors.email}>
                    <Label>Email</Label>
                    <Input
                        type="email"
                        icon={<RiMailLine size={18} />}
                        autoComplete="email"
                        autoFocus
                        required
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        error={!!errors.email}
                    />
                    <FieldError>{errors.email}</FieldError>
                </Field>
                <Field error={!!errors.password}>
                    <div className="flex items-center justify-between">
                        <Label>Mot de passe</Label> 
                        <Button
                            type="button"
                            variant="text"
                            size="inline"
                            onClick={openForgotPassword}
                            className="self-start"
                        >
                            Mot de passe oublié ?
                        </Button>
                    </div>
                    
                    <Input
                        type="password"
                        icon={<RiLockPasswordLine size={18} />}
                        autoComplete="current-password"
                        required
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        error={!!errors.password}
                    />
                    <FieldError>{errors.password}</FieldError>
                </Field>
                
                <Button
                    type="submit"
                    disabled={processing}
                    className="mt-2 py-3"
                >
                    {processing ? 'Connexion...' : 'Se connecter'}
                </Button>
                <p className="text-center text-body">
                    Pas encore de compte ?{' '}
                    <button
                        type="button"
                        onClick={() => openRegister()}
                        className="font-mono text-lg font-medium text-action underline decoration-1 underline-offset-4 hover:decoration-2"
                    >
                        Créer un compte
                    </button>
                </p>
            </form>
        </Modal>
    );
}
