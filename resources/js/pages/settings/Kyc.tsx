import { Head, useForm } from '@inertiajs/react';
import { RiFileTextLine, RiShieldCheckLine } from '@remixicon/react';
import { FormEvent } from 'react';
import { Button } from '@/components/Ui/Button';
import { FieldError } from '@/components/Ui/FieldError';
import { Input } from '@/components/Ui/Input';
import { Label } from '@/components/Ui/Label';

type Props = {
    status: string;
    rejectionReason: string | null;
    submittedAt: string | null;
    documents: { id: number; name: string; downloadUrl: string }[];
};

const statusStyles: Record<string, string> = {
    draft: 'bg-slate-100 text-slate-600',
    submitted: 'bg-orange-50 text-orange-700',
    under_review: 'bg-orange-50 text-orange-700',
    approved: 'bg-blue-50 text-blue-700',
    rejected: 'bg-orange-100 text-orange-700',
    expired: 'bg-slate-100 text-slate-400',
};

const statusLabels: Record<string, string> = {
    draft: 'Brouillon', submitted: 'Soumis', under_review: 'En cours de vérification',
    approved: 'Approuvé', rejected: 'Rejeté', expired: 'Expiré',
};

export default function Kyc({ status, rejectionReason, submittedAt, documents }: Props) {
    const { data, setData, post, processing, errors } = useForm<{ files: File[] }>({ files: [] });

    function submit(e: FormEvent) {
        e.preventDefault();
        post('/settings/kyc', { forceFormData: true });
    }

    const canSubmit = status === 'draft' || status === 'rejected';

    return (
        <div className="mx-auto max-w-2xl px-6 py-12">
            <Head title="Vérification KYC" />
            <p className="text-eyebrow">Paramètres</p>
            <h1 className="mt-2 mb-2 text-page-title">Vérification d'identité (KYC)</h1>
            <p className="mb-6 text-body">
                Vos documents sont stockés sur un espace privé, jamais accessibles publiquement, et
                téléchargeables uniquement via des liens temporaires et signés (spec §43).
            </p>

            <div className={`mb-6 inline-flex items-center gap-2 px-3 py-1.5 font-mono text-xs uppercase ${statusStyles[status]}`}>
                <RiShieldCheckLine size={14} /> {statusLabels[status]}
            </div>

            {status === 'rejected' && rejectionReason && (
                <div className="mb-6 border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-700">
                    <strong>Motif du rejet :</strong> {rejectionReason}
                </div>
            )}
            {submittedAt && (
                <p className="mb-6 text-caption">Soumis le {new Date(submittedAt).toLocaleDateString('fr-FR')}</p>
            )}

            {documents.length > 0 && (
                <div className="mb-8 border border-slate-300">
                    {documents.map((d) => (
                        <div key={d.id} className="flex items-center justify-between border-b border-slate-300 p-3 text-sm last:border-b-0">
                            <span className="flex items-center gap-2"><RiFileTextLine size={16} className="text-slate-400" /> {d.name}</span>
                            <a href={d.downloadUrl} className="font-mono text-xs text-blue-600 hover:text-blue-700">Télécharger</a>
                        </div>
                    ))}
                </div>
            )}

            {canSubmit && (
                <form onSubmit={submit} className="flex flex-col gap-4 border border-slate-300 p-5">
                    <div>
                        <Label>
                            Pièce d'identité, documents d'entreprise, justificatifs (PDF, JPG, PNG — 10 Mo max chacun)
                        </Label>
                        <Input
                            type="file"
                            multiple
                            onChange={(e) => setData('files', Array.from(e.target.files ?? []))}
                            error={!!errors.files}
                        />
                        <FieldError>{errors.files}</FieldError>
                    </div>
                    <Button type="submit" disabled={processing || data.files.length === 0} className="self-start px-6 py-3">
                        {processing ? 'Envoi...' : 'Soumettre pour vérification'}
                    </Button>
                </form>
            )}
        </div>
    );
}
