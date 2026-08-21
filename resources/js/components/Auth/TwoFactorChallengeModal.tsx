import { useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { Modal } from '@/components/Modal';
import { Button } from '@/components/Ui/Button';
import { FieldError } from '@/components/Ui/FieldError';
import { Input } from '@/components/Ui/Input';
import { Label } from '@/components/Ui/Label';

export function TwoFactorChallengeModal({ open, onClose }: { open: boolean; onClose: () => void }) {
    const [useRecoveryCode, setUseRecoveryCode] = useState(false);
    const { data, setData, post, processing, errors } = useForm({ code: '', recovery_code: '' });

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/two-factor-challenge', { onSuccess: onClose }); // Fortify
    }

    return (
        <Modal open={open} onClose={onClose} title="Vérification en deux étapes">
            <form onSubmit={submit} className="flex flex-col gap-4">
                {useRecoveryCode ? (
                    <div>
                        <Label>Code de récupération</Label>
                        <Input
                            autoFocus
                            value={data.recovery_code}
                            onChange={(e) => setData('recovery_code', e.target.value)}
                            error={!!errors.recovery_code}
                        />
                        <FieldError>{errors.recovery_code}</FieldError>
                    </div>
                ) : (
                    <div>
                        <Label>Code à 6 chiffres</Label>
                        <Input
                            autoFocus
                            inputMode="numeric"
                            value={data.code}
                            onChange={(e) => setData('code', e.target.value)}
                            error={!!errors.code}
                            className="text-center text-lg tracking-[0.5em]"
                        />
                        <FieldError>{errors.code}</FieldError>
                    </div>
                )}
                <Button type="button" variant="text" size="inline" onClick={() => setUseRecoveryCode((v) => !v)} className="self-start">
                    {useRecoveryCode ? "Utiliser l'app d'authentification" : 'Utiliser un code de récupération'}
                </Button>
                <Button type="submit" disabled={processing} className="py-3">
                    {processing ? 'Vérification...' : 'Vérifier'}
                </Button>
            </form>
        </Modal>
    );
}
