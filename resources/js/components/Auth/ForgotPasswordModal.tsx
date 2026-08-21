import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { Modal } from '@/components/Modal';
import { Button } from '@/components/Ui/Button';
import { FieldError } from '@/components/Ui/FieldError';
import { Input } from '@/components/Ui/Input';
import { Label } from '@/components/Ui/Label';

export function ForgotPasswordModal({ open, onClose }: { open: boolean; onClose: () => void }) {
    const { data, setData, post, processing, errors, recentlySuccessful } = useForm({ email: '' });

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/forgot-password'); // Fortify
    }

    return (
        <Modal open={open} onClose={onClose} title="Mot de passe oublié" description="Recevez un lien de réinitialisation par email.">
            {recentlySuccessful ? (
                <p className="border border-blue-200 bg-blue-50 px-3 py-3 text-sm text-blue-700">
                    Si un compte existe pour cet email, un lien a été envoyé.
                </p>
            ) : (
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
                    <Button type="submit" disabled={processing} className="py-3">
                        {processing ? 'Envoi...' : 'Envoyer le lien'}
                    </Button>
                </form>
            )}
        </Modal>
    );
}
