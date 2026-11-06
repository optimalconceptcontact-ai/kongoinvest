import { useForm } from '@inertiajs/react';
import { SeoHead } from '@/components/SeoHead';
import { Button } from '@/components/ui/button';
import { PublicLayout } from '@/layouts/PublicLayout';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing, recentlySuccessful } = useForm({});

    return (
        <PublicLayout>
            <SeoHead
                title="Vérifiez votre email"
                description="Vérifiez votre adresse email pour activer votre compte KongoInvest."
            />
            <div className="mx-auto max-w-xl px-4 py-20 text-center md:px-6">
                <div className="mx-auto mb-6 h-1 w-12 bg-signal" />
                <h1 className="text-page-title">Vérifiez votre email</h1>
                <p className="mt-4 text-body">
                    Un lien de vérification a été envoyé à votre adresse email.
                    Cliquez dessus pour activer votre compte.
                </p>
                {(recentlySuccessful ||
                    status === 'verification-link-sent') && (
                    <p className="mx-auto mt-4 max-w-md border border-rule-strong bg-surface-2 px-3 py-2 text-xl text-ink-muted">
                        Un nouveau lien vient d'être envoyé.
                    </p>
                )}
                <Button
                    type="button"
                    disabled={processing}
                    onClick={() => post('/email/verification-notification')}
                    className="mt-6 px-8 py-3"
                >
                    {processing ? 'Envoi...' : "Renvoyer l'email"}
                </Button>
            </div>
        </PublicLayout>
    );
}
