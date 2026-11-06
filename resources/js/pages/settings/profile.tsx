import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { RiLockPasswordLine, RiMailLine, RiUserLine } from '@remixicon/react';
import { BackLink } from '@/components/ui/BackLink';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/Field';
import { FieldError } from '@/components/ui/FieldError';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/Label';
import { PhoneInput } from '@/components/ui/PhoneInput';

type Props = {
    user: {
        id: number;
        name: string;
        email: string;
        phone: string | null;
        email_verified_at: string | null;
    };
};

export default function Profile({ user }: Props) {
    const { data, setData, put, processing, errors, recentlySuccessful } =
        useForm({
            name: user.name,
            email: user.email,
            phone: user.phone ?? '',
        });

    const deleteForm = useForm({ password: '' });
    const [confirmingDeletion, setConfirmingDeletion] = useState(false);

    function submit(e: FormEvent) {
        e.preventDefault();
        put('/user/profile-information'); // Fortify — PUT only, PATCH 405s
    }

    function submitDeletion(e: FormEvent) {
        e.preventDefault();
        deleteForm.delete('/settings/profile', {
            onError: () => {},
            onSuccess: () => setConfirmingDeletion(false),
        });
    }

    return (
        <>
            <Head title="Mon compte" />
            <div className="mx-auto max-w-2xl px-6 py-12">
                <BackLink href="/dashboard" />
                <p className="text-eyebrow">Paramètres</p>
                <h1 className="mt-2 mb-8 text-page-title">Mon compte</h1>

                {recentlySuccessful && (
                    <div className="mb-6 border border-rule-strong bg-surface-2 px-4 py-3 text-xl leading-6 text-ink-muted">
                        Informations mises à jour.
                    </div>
                )}
                {!user.email_verified_at && (
                    <div className="mb-6 border border-rule-strong bg-surface-2 px-4 py-3 text-xl leading-6 text-ink-muted">
                        Votre email n'est pas encore vérifié.
                    </div>
                )}

                <form onSubmit={submit} className="flex flex-col gap-5">
                    <Field error={!!errors.name}>
                        <Label>Nom complet</Label>
                        <Input
                            icon={<RiUserLine size={18} />}
                            autoComplete="name"
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
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            error={!!errors.email}
                        />
                        <FieldError>{errors.email}</FieldError>
                    </Field>
                    <Field error={!!errors.phone}>
                        <Label>Téléphone</Label>
                        <PhoneInput
                            autoComplete="tel"
                            value={data.phone}
                            onChange={(phone) => setData('phone', phone)}
                            error={!!errors.phone}
                        />
                        <FieldError>{errors.phone}</FieldError>
                    </Field>
                    <Button
                        type="submit"
                        disabled={processing}
                        className="self-start px-6 py-3"
                    >
                        {processing ? 'Enregistrement...' : 'Enregistrer'}
                    </Button>
                </form>

                <div className="mt-16 border border-danger/30 bg-danger/5 p-6">
                    <h2 className="mb-1 text-btn text-danger">
                        Zone de danger
                    </h2>
                    <p className="mb-4 text-body">
                        La suppression de votre compte est irréversible.
                    </p>

                    {confirmingDeletion ? (
                        <form
                            onSubmit={submitDeletion}
                            className="flex flex-col gap-3"
                        >
                            <Field error={!!deleteForm.errors.password}>
                                <Label>Mot de passe</Label>
                                <Input
                                    type="password"
                                    icon={<RiLockPasswordLine size={18} />}
                                    autoComplete="current-password"
                                    autoFocus
                                    placeholder="Confirmez votre mot de passe"
                                    value={deleteForm.data.password}
                                    onChange={(e) =>
                                        deleteForm.setData(
                                            'password',
                                            e.target.value,
                                        )
                                    }
                                    error={!!deleteForm.errors.password}
                                />
                                <FieldError>
                                    {deleteForm.errors.password}
                                </FieldError>
                            </Field>
                            <div className="flex gap-2">
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={deleteForm.processing}
                                >
                                    Supprimer définitivement
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setConfirmingDeletion(false)}
                                >
                                    Annuler
                                </Button>
                            </div>
                        </form>
                    ) : (
                        <Button
                            type="button"
                            variant="destructive-outline"
                            onClick={() => setConfirmingDeletion(true)}
                        >
                            Supprimer mon compte
                        </Button>
                    )}
                </div>
            </div>
        </>
    );
}
