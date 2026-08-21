import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { Modal } from '@/components/Modal';
import { Button } from '@/components/Ui/Button';
import { FieldError } from '@/components/Ui/FieldError';
import { Input } from '@/components/Ui/Input';
import { Label } from '@/components/Ui/Label';

type Props = { open: boolean; onClose: () => void; token?: string; email?: string };

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
        <Modal open={open} onClose={onClose} title="Réinitialiser le mot de passe">
            <form onSubmit={submit} className="flex flex-col gap-4">
                <div>
                    <Label>Nouveau mot de passe</Label>
                    <Input
                        type="password"
                        autoFocus
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        error={!!errors.password}
                    />
                    <FieldError>{errors.password}</FieldError>
                </div>
                <div>
                    <Label>Confirmer</Label>
                    <Input
                        type="password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                    />
                </div>
                <Button type="submit" disabled={processing} className="py-3">
                    {processing ? 'Enregistrement...' : 'Réinitialiser'}
                </Button>
            </form>
        </Modal>
    );
}
