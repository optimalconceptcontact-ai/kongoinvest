import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { Modal } from '@/components/Modal';
import { Button } from '@/components/Ui/Button';
import { Input } from '@/components/Ui/Input';
import { useAuthModal } from '@/hooks/useAuthModal';

/**
 * Required before sensitive operations — deleting a project, releasing
 * Escrow funds, changing a role, issuing a manual refund (spec §89).
 * On success we dispatch the same resume-intent event as login/register
 * so the originating action can proceed exactly once.
 */
export function ConfirmPasswordModal({ open, onClose }: { open: boolean; onClose: () => void }) {
    const { consumeIntent } = useAuthModal();
    const { data, setData, post, processing, errors } = useForm({ password: '' });

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/user/confirm-password', {
            onSuccess: () => {
                onClose();
                const intent = consumeIntent();
                if (intent) window.dispatchEvent(new CustomEvent('kongoinvestment:resume-intent', { detail: intent }));
            },
        });
    }

    return (
        <Modal open={open} onClose={onClose} title="Confirmez votre mot de passe" description="Action sensible — merci de confirmer votre identité.">
            <form onSubmit={submit} className="flex flex-col gap-4">
                <Input
                    type="password"
                    autoFocus
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    error={!!errors.password}
                />
                {errors.password && <p className="text-xs text-orange-600">{errors.password}</p>}
                <Button type="submit" disabled={processing} className="py-3">
                    {processing ? 'Vérification...' : 'Confirmer'}
                </Button>
            </form>
        </Modal>
    );
}
