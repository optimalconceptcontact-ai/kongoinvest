import { useForm } from '@inertiajs/react';
import { RiLockPasswordLine } from '@remixicon/react';
import type { FormEvent } from 'react';
import { Modal } from '@/components/Modal';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/Field';
import { FieldError } from '@/components/ui/FieldError';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/Label';

type Props = {
    open: boolean;
    onClose: () => void;
    token?: string;
    email?: string;
};

export function ResetPasswordModal({ open, onClose, token, email }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        token: token ?? '',
        email: email ?? '',
        password: '',
        password_confirmation: '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/reset-password', { onSuccess: onClose }); // Fortify
    }

    return (
        <Modal
            open={open}
            onClose={onClose}
            title="Réinitialiser le mot de passe"
        >
            <form onSubmit={submit} className="flex flex-col gap-4">
                <Field error={!!errors.password}>
                    <Label>Nouveau mot de passe</Label>
                    <Input
                        type="password"
                        icon={<RiLockPasswordLine size={18} />}
                        autoComplete="new-password"
                        autoFocus
                        required
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        error={!!errors.password}
                    />
                    <FieldError>{errors.password}</FieldError>
                </Field>
                <Field>
                    <Label>Confirmer</Label>
                    <Input
                        type="password"
                        icon={<RiLockPasswordLine size={18} />}
                        autoComplete="new-password"
                        required
                        value={data.password_confirmation}
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                    />
                </Field>
                <Button type="submit" disabled={processing} className="py-3">
                    {processing ? 'Enregistrement...' : 'Réinitialiser'}
                </Button>
            </form>
        </Modal>
    );
}
