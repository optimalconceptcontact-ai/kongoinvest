import { Head, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { Button } from '@/components/Ui/Button';
import { FieldError } from '@/components/Ui/FieldError';
import { Input } from '@/components/Ui/Input';
import { Label } from '@/components/Ui/Label';

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
    const { data, setData, patch, processing, errors, recentlySuccessful } = useForm({
        name: user.name,
        email: user.email,
        phone: user.phone ?? '',
    });

    const deleteForm = useForm({ password: '' });
    const [confirmingDeletion, setConfirmingDeletion] = useState(false);

    function submit(e: FormEvent) {
        e.preventDefault();
        patch('/user/profile-information'); // Fortify
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
                <p className="text-eyebrow">Paramètres</p>
                <h1 className="mt-2 mb-8 text-page-title">Mon compte</h1>

                {recentlySuccessful && (
                    <div className="mb-6 border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                        Informations mises à jour.
                    </div>
                )}
                {!user.email_verified_at && (
                    <div className="mb-6 border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-700">
                        Votre email n'est pas encore vérifié.
                    </div>
                )}

                <form onSubmit={submit} className="flex flex-col gap-5">
                    <div>
                        <Label>Nom complet</Label>
                        <Input value={data.name} onChange={(e) => setData('name', e.target.value)} error={!!errors.name} />
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
                    <div>
                        <Label>Téléphone</Label>
                        <Input value={data.phone} onChange={(e) => setData('phone', e.target.value)} />
                    </div>
                    <Button type="submit" disabled={processing} className="self-start px-6 py-3">
                        {processing ? 'Enregistrement...' : 'Enregistrer'}
                    </Button>
                </form>

                <div className="mt-16 border border-orange-200 p-6">
                    <h2 className="mb-1 text-sm font-bold text-orange-700">Zone de danger</h2>
                    <p className="mb-4 text-body">
                        La suppression de votre compte est irréversible.
                    </p>

                    {confirmingDeletion ? (
                        <form onSubmit={submitDeletion} className="flex flex-col gap-3">
                            <Input
                                type="password"
                                autoFocus
                                placeholder="Confirmez votre mot de passe"
                                value={deleteForm.data.password}
                                onChange={(e) => deleteForm.setData('password', e.target.value)}
                                className="focus:border-orange-600"
                            />
                            <FieldError>{deleteForm.errors.password}</FieldError>
                            <div className="flex gap-2">
                                <Button type="submit" variant="destructive" disabled={deleteForm.processing}>
                                    Supprimer définitivement
                                </Button>
                                <button
                                    type="button"
                                    onClick={() => setConfirmingDeletion(false)}
                                    className="border border-slate-300 px-4 py-2 text-sm"
                                >
                                    Annuler
                                </button>
                            </div>
                        </form>
                    ) : (
                        <Button type="button" variant="destructive-outline" onClick={() => setConfirmingDeletion(true)}>
                            Supprimer mon compte
                        </Button>
                    )}
                </div>
            </div>
        </>
    );
}
