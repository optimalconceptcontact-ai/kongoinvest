import { useForm } from '@inertiajs/react';
import { Modal } from '@/components/Modal';
import { Button } from '@/components/ui/button';

export function VerifyEmailModal({
    open,
    onClose,
}: {
    open: boolean;
    onClose: () => void;
}) {
    const { post, processing, recentlySuccessful } = useForm({});

    return (
        <Modal open={open} onClose={onClose} title="Vérifiez votre email">
            <p className="mb-4 text-body">
                Un lien de vérification a été envoyé à votre adresse email.
                Cliquez dessus pour activer votre compte.
            </p>
            {recentlySuccessful && (
                <p className="mb-4 border border-rule-strong bg-surface-2 px-3 py-2 text-lg leading-5 text-ink-muted">
                    Un nouveau lien vient d'être envoyé.
                </p>
            )}
            <Button
                type="button"
                disabled={processing}
                onClick={() => post('/email/verification-notification')} // Fortify
                className="w-full py-3"
            >
                {processing ? 'Envoi...' : "Renvoyer l'email"}
            </Button>
        </Modal>
    );
}
