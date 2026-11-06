import { useForm } from '@inertiajs/react';
import { RiMailLine } from '@remixicon/react';
import type { FormEvent } from 'react';
import { Modal } from '@/components/Modal';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/Field';
import { FieldError } from '@/components/ui/FieldError';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/Label';

export function ForgotPasswordModal({
    open,
    onClose,
}: {
    open: boolean;
    onClose: () => void;
}) {
    const { data, setData, post, processing, errors, recentlySuccessful } =
        useForm({ email: '' });

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/forgot-password'); // Fortify
    }

    return (
        <Modal
            open={open}
            onClose={onClose}
            title="Mot de passe oublié"
            description="Recevez un lien de réinitialisation par email."
        >
            {recentlySuccessful ? (
                <p className="border border-rule-strong bg-surface-2 px-3 py-3 text-lg leading-5 text-ink-muted">
                    Si un compte existe pour cet email, un lien a été envoyé.
                </p>
            ) : (
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
                    <Button
                        type="submit"
                        disabled={processing}
                        className="py-3"
                    >
                        {processing ? 'Envoi...' : 'Envoyer le lien'}
                    </Button>
                </form>
            )}
        </Modal>
    );
}
