import { useForm } from '@inertiajs/react';
import { RiKey2Line, RiShieldKeyholeLine } from '@remixicon/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { Modal } from '@/components/Modal';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/Field';
import { FieldError } from '@/components/ui/FieldError';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/Label';

export function TwoFactorChallengeModal({
    open,
    onClose,
}: {
    open: boolean;
    onClose: () => void;
}) {
    const [useRecoveryCode, setUseRecoveryCode] = useState(false);
    const { data, setData, post, processing, errors } = useForm({
        code: '',
        recovery_code: '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/two-factor-challenge', { onSuccess: onClose }); // Fortify
    }

    return (
        <Modal
            open={open}
            onClose={onClose}
            title="Vérification en deux étapes"
        >
            <form onSubmit={submit} className="flex flex-col gap-4">
                {useRecoveryCode ? (
                    <Field error={!!errors.recovery_code}>
                        <Label>Code de récupération</Label>
                        <Input
                            icon={<RiKey2Line size={18} />}
                            autoComplete="one-time-code"
                            autoFocus
                            required
                            value={data.recovery_code}
                            onChange={(e) =>
                                setData('recovery_code', e.target.value)
                            }
                            error={!!errors.recovery_code}
                        />
                        <FieldError>{errors.recovery_code}</FieldError>
                    </Field>
                ) : (
                    <Field error={!!errors.code}>
                        <Label>Code à 6 chiffres</Label>
                        <Input
                            icon={<RiShieldKeyholeLine size={18} />}
                            autoComplete="one-time-code"
                            autoFocus
                            inputMode="numeric"
                            pattern="[0-9]*"
                            required
                            value={data.code}
                            onChange={(e) => setData('code', e.target.value)}
                            error={!!errors.code}
                            className="text-center text-2xl tracking-[0.5em]"
                        />
                        <FieldError>{errors.code}</FieldError>
                    </Field>
                )}
                <Button
                    type="button"
                    variant="text"
                    size="inline"
                    onClick={() => setUseRecoveryCode((v) => !v)}
                    className="self-start"
                >
                    {useRecoveryCode
                        ? "Utiliser l'app d'authentification"
                        : 'Utiliser un code de récupération'}
                </Button>
                <Button type="submit" disabled={processing} className="py-3">
                    {processing ? 'Vérification...' : 'Vérifier'}
                </Button>
            </form>
        </Modal>
    );
}
