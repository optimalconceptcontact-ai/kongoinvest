import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { Modal } from '@/components/Modal';
import { Button } from '@/components/Ui/Button';
import { FieldError } from '@/components/Ui/FieldError';
import { Input } from '@/components/Ui/Input';
import { Label } from '@/components/Ui/Label';
import { useAuthModal } from '@/hooks/useAuthModal';

type Role = 'entrepreneur' | 'investor';

export function RegisterModal({ open, onClose }: { open: boolean; onClose: () => void }) {
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
                if (intent) window.dispatchEvent(new CustomEvent('kanda:resume-intent', { detail: intent }));
            },
        });
    }

    // Exactly two registrable roles — "Professional" is a subscription
    // plan (see /pricing), not a third registration option. Either role
    // above can subscribe to it after signing up.
    const roles: { value: Role; label: string; hint: string }[] = [
        { value: 'entrepreneur', label: 'Entrepreneur', hint: 'Mon projet est basé en RDC' },
        { value: 'investor', label: 'Investisseur', hint: 'Je cherche à financer des projets' },
    ];

    return (
        <Modal open={open} onClose={onClose} title="Créer un compte" maxWidth="md">
            <form onSubmit={submit} className="flex flex-col gap-4">
                {pendingIntent && (
                    <p className="border border-orange-200 bg-orange-50 px-3 py-2 text-xs text-orange-700">
                        Créez votre compte pour continuer — nous reprendrons votre action juste après.
                    </p>
                )}

                <div className="grid grid-cols-2 gap-2">
                    {roles.map((r) => (
                        <button
                            type="button"
                            key={r.value}
                            onClick={() => setData('role', r.value)}
                            className={`border px-3 py-3 text-left text-xs transition ${
                                data.role === r.value
                                    ? 'border-slate-900 bg-slate-900 text-white'
                                    : 'border-slate-300 text-slate-700 hover:border-slate-900'
                            }`}
                        >
                            <div className="font-semibold">{r.label}</div>
                            <div className={`mt-1 ${data.role === r.value ? 'text-slate-300' : 'text-slate-500'}`}>
                                {r.hint}
                            </div>
                        </button>
                    ))}
                </div>
                {errors.role && <p className="text-xs text-orange-600">{errors.role}</p>}

                <div>
                    <Label>Nom complet</Label>
                    <Input
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        error={!!errors.name}
                    />
                    <FieldError>{errors.name}</FieldError>
                </div>
                <div>
                    <Label>Email</Label>
                    <Input
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        error={!!errors.email}
                    />
                    <FieldError>{errors.email}</FieldError>
                </div>
                <div className="grid grid-cols-2 gap-3">
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
                    <div>
                        <Label>Confirmer</Label>
                        <Input
                            type="password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                        />
                    </div>
                </div>

                <Button type="submit" variant="cta" disabled={processing} className="mt-2 py-3">
                    {processing ? 'Création...' : 'Créer mon compte'}
                </Button>
                <p className="text-center text-body">
                    Déjà un compte ?{' '}
                    <button type="button" onClick={() => openLogin()} className="font-medium text-blue-600 hover:text-blue-700">
                        Se connecter
                    </button>
                </p>
            </form>
        </Modal>
    );
}
