import { Head, useForm } from '@inertiajs/react';
import { RiFileTextLine, RiShieldCheckLine } from '@remixicon/react';
import type { FormEvent } from 'react';
import { BackLink } from '@/components/ui/BackLink';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/Field';
import { FieldError } from '@/components/ui/FieldError';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/Label';

type Props = {
    status: string;
    rejectionReason: string | null;
    submittedAt: string | null;
    documents: { id: number; name: string; downloadUrl: string }[];
};

const statusStyles: Record<string, string> = {
    draft: 'bg-surface-2 text-ink-muted ',
    submitted: 'bg-surface-2 text-ink ',
    under_review: 'bg-surface-2 text-ink ',
    approved: 'bg-surface-2 text-ink ',
    rejected: 'bg-signal/20 text-ink',
    expired: 'bg-surface-2 text-ink-muted ',
};

const statusLabels: Record<string, string> = {
    draft: 'Brouillon',
    submitted: 'Soumis',
    under_review: 'En cours de vérification',
    approved: 'Approuvé',
    rejected: 'Rejeté',
    expired: 'Expiré',
};

export default function Kyc({
    status,
    rejectionReason,
    submittedAt,
    documents,
}: Props) {
    const { data, setData, post, processing, errors } = useForm<{
        files: File[];
    }>({ files: [] });

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/settings/kyc', { forceFormData: true });
    }

    const canSubmit = status === 'draft' || status === 'rejected';

    return (
        <div className="mx-auto max-w-2xl px-6 py-12">
            <Head title="Vérification KYC" />
            <BackLink href="/dashboard" />
            <p className="text-eyebrow">Paramètres</p>
            <h1 className="mt-2 mb-2 text-page-title">
                Vérification d'identité (KYC)
            </h1>
            <p className="mb-6 text-body">
                Vos documents sont stockés sur un espace privé, jamais
                accessibles publiquement, et téléchargeables uniquement via des
                liens temporaires et signés (spec §43).
            </p>

            <div
                className={`mb-6 inline-flex items-center gap-2 px-3 py-1.5 font-mono text-lg uppercase ${statusStyles[status]}`}
            >
                <RiShieldCheckLine size={14} /> {statusLabels[status]}
            </div>

            {status === 'rejected' && rejectionReason && (
                <div className="mb-6 border border-rule-strong bg-surface-2 px-4 py-3 text-lg leading-5 text-ink-muted">
                    <strong>Motif du rejet :</strong> {rejectionReason}
                </div>
            )}
            {submittedAt && (
                <p className="mb-6 text-caption">
                    Soumis le{' '}
                    {new Date(submittedAt).toLocaleDateString('fr-FR')}
                </p>
            )}

            {documents.length > 0 && (
                <div className="mb-8 border border-rule">
                    {documents.map((d) => (
                        <div
                            key={d.id}
                            className="flex items-center justify-between border-b border-rule p-3 text-lg last:border-b-0"
                        >
                            <span className="flex items-center gap-2">
                                <RiFileTextLine
                                    size={16}
                                    className="text-ink-muted"
                                />{' '}
                                {d.name}
                            </span>
                            <a
                                href={d.downloadUrl}
                                className="font-mono text-lg text-action underline decoration-1 underline-offset-4 hover:decoration-2"
                            >
                                Télécharger
                            </a>
                        </div>
                    ))}
                </div>
            )}

            {canSubmit && (
                <form
                    onSubmit={submit}
                    className="flex flex-col gap-4 border border-rule p-5"
                >
                    <Field error={!!errors.files}>
                        <Label>
                            Pièce d'identité, documents d'entreprise,
                            justificatifs (PDF, JPG, PNG — 10 Mo max chacun)
                        </Label>
                        <Input
                            type="file"
                            multiple
                            accept=".pdf,.jpg,.jpeg,.png"
                            onChange={(e) =>
                                setData(
                                    'files',
                                    Array.from(e.target.files ?? []),
                                )
                            }
                            error={!!errors.files}
                        />
                        <FieldError>{errors.files}</FieldError>
                    </Field>
                    <Button
                        type="submit"
                        disabled={processing || data.files.length === 0}
                        className="self-start px-6 py-3"
                    >
                        {processing
                            ? 'Envoi...'
                            : 'Soumettre pour vérification'}
                    </Button>
                </form>
            )}
        </div>
    );
}
