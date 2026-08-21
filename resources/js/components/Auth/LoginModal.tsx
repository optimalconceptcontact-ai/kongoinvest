import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { Modal } from '@/components/Modal';
import { Button } from '@/components/Ui/Button';
import { FieldError } from '@/components/Ui/FieldError';
import { Input } from '@/components/Ui/Input';
import { Label } from '@/components/Ui/Label';
import { useAuthModal } from '@/hooks/useAuthModal';

export function LoginModal({ open, onClose }: { open: boolean; onClose: () => void }) {
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
                if (intent) window.dispatchEvent(new CustomEvent('kanda:resume-intent', { detail: intent }));
            },
        });
    }

    return (
        <Modal open={open} onClose={onClose} title="Se connecter" maxWidth="sm">
            <form onSubmit={submit} className="flex flex-col gap-4">
                <div>
                    <Label>Email</Label>
                    <Input
                        type="email"
                        autoFocus
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        error={!!errors.email}
                    />
                    <FieldError>{errors.email}</FieldError>
                </div>
                <div>
                    <Label>Mot de passe</Label>
                    <Input
                        type="password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        error={!!errors.password}
                    />
                    <FieldError>{errors.password}</FieldError>
                </div>
                <Button type="button" variant="text" size="inline" onClick={openForgotPassword} className="self-start">
                    Mot de passe oublié ?
                </Button>
                <Button type="submit" disabled={processing} className="mt-2 py-3">
                    {processing ? 'Connexion...' : 'Se connecter'}
                </Button>
                <p className="text-center text-body">
                    Pas encore de compte ?{' '}
                    <button type="button" onClick={() => openRegister()} className="font-medium text-blue-600 hover:text-blue-700">
                        Créer un compte
                    </button>
                </p>
            </form>
        </Modal>
    );
}
