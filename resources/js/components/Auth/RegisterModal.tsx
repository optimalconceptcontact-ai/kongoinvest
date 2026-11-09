import { useForm } from '@inertiajs/react';
import { RiLockPasswordLine, RiMailLine, RiUserLine } from '@remixicon/react';
import type { FormEvent } from 'react';
import { Modal } from '@/components/Modal';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/Field';
import { FieldError } from '@/components/ui/FieldError';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/Label';
import { useAuthModal } from '@/hooks/useAuthModal';

type Role = 'entrepreneur' | 'investor';

export function RegisterModal({
    open,
    onClose,
}: {
    open: boolean;
    onClose: () => void;
}) {
    const { openLogin, pendingIntent, consumeIntent } = useAuthModal();
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'entrepreneur' as Role,
    });

    function submit(e: FormEvent) {
        e.preventDefault();

        // POST /register (Fortify). The `role` field is validated and
        // assigned server-side via Spatie Permission — it is never
        // trusted as-is from the client (spec §8-9).
        post('/register', {
            onSuccess: () => {
                reset('password', 'password_confirmation');
                onClose();
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

    // Exactly two registrable roles — "Professional" is a subscription
    // plan (see /pricing), not a third registration option. Either role
    // above can subscribe to it after signing up.
    const roles: { value: Role; label: string; hint: string }[] = [
        {
            value: 'entrepreneur',
            label: 'Entrepreneur',
            hint: 'Mon projet est basé en RDC',
        },
        {
            value: 'investor',
            label: 'Investisseur',
            hint: 'Je cherche à financer des projets',
        },
    ];

    return (
        <Modal
            open={open}
            onClose={onClose}
            title="Créer un compte"
            maxWidth="md"
        >
            <form onSubmit={submit} className="flex flex-col gap-4">
                {pendingIntent && (
                    <p className="border border-rule-strong bg-surface-2 px-3 py-2 text-lg leading-5 text-ink-muted">
                        Créez votre compte pour continuer — nous reprendrons
                        votre action juste après.
                    </p>
                )}

                <div
                    className="grid grid-cols-2 gap-2"
                    role="radiogroup"
                    aria-label="Type de compte"
                >
                    {roles.map((r) => (
                        <button
                            type="button"
                            key={r.value}
                            role="radio"
                            aria-checked={data.role === r.value}
                            onClick={() => setData('role', r.value)}
                            className={`border px-3 py-3 text-left text-sm transition ${
                                data.role === r.value
                                    ? 'border-ink bg-ink text-paper'
                                    : 'border-rule-strong text-ink hover:border-ink'
                            }`}
                        >
                            <div className="font-mono font-semibold tracking-[0.04em] uppercase">
                                {r.label}
                            </div>
                            <div
                                className={`mt-1 ${data.role === r.value ? 'text-paper/70' : 'text-ink-muted'}`}
                            >
                                {r.hint}
                            </div>
                        </button>
                    ))}
                </div>
                {errors.role && (
                    <p className="text-lg text-danger">{errors.role}</p>
                )}

                <Field error={!!errors.name}>
                    <Label>Nom complet</Label>
                    <Input
                        icon={<RiUserLine size={18} />}
                        autoComplete="name"
                        required
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        error={!!errors.name}
                    />
                    <FieldError>{errors.name}</FieldError>
                </Field>
                <Field error={!!errors.email}>
                    <Label>Email</Label>
                    <Input
                        type="email"
                        icon={<RiMailLine size={18} />}
                        autoComplete="email"
                        required
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        error={!!errors.email}
                    />
                    <FieldError>{errors.email}</FieldError>
                </Field>
                <div className="grid grid-cols-2 gap-3">
                    <Field error={!!errors.password}>
                        <Label>Mot de passe</Label>
                        <Input
                            type="password"
                            icon={<RiLockPasswordLine size={18} />}
                            autoComplete="new-password"
                            required
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
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
                </div>

                <Button
                    type="submit"
                    variant="cta"
                    disabled={processing}
                    className="mt-2 py-3"
                >
                    {processing ? 'Création...' : 'Créer mon compte'}
                </Button>
                <p className="text-center text-body">
                    Déjà un compte ?{' '}
                    <button
                        type="button"
                        onClick={() => openLogin()}
                        className="font-mono text-lg font-medium text-action underline decoration-1 underline-offset-4 hover:decoration-2"
                    >
                        Se connecter
                    </button>
                </p>
            </form>
        </Modal>
    );
}
