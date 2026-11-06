import { Head, router } from '@inertiajs/react';
import { RiErrorWarningLine, RiFileTextLine } from '@remixicon/react';
import { useState } from 'react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Profile = {
    id: number;
    userName: string;
    userEmail: string;
    status: string;
    submittedAt: string | null;
    documents: { id: number; name: string; downloadUrl: string }[];
};

function RejectForm({ id, onDone }: { id: number; onDone: () => void }) {
    const [reason, setReason] = useState('');

    function submit() {
        if (!reason.trim()) return;
        router.post(
            `/admin/kyc/${id}/reject`,
            { reason },
            { onSuccess: onDone },
        );
    }

    return (
        <div className="mt-2 flex gap-2">
            <Input
                size="sm"
                icon={<RiErrorWarningLine size={18} />}
                className="flex-1 text-lg"
                value={reason}
                onChange={(e) => setReason(e.target.value)}
                placeholder="Motif du rejet"
            />
            <Button onClick={submit} variant="destructive" size="sm">
                Confirmer le rejet
            </Button>
        </div>
    );
}

export default function Index({ profiles }: { profiles: Profile[] }) {
    const [rejecting, setRejecting] = useState<number | null>(null);

    function approve(id: number) {
        router.post(`/admin/kyc/${id}/approve`);
    }

    return (
        <AdminLayout title="Vérifications KYC">
            <Head title="Admin — KYC" />

            {profiles.length === 0 ? (
                <div className="border border-rule-strong bg-paper p-6 text-body">
                    Aucun dossier en attente de vérification.
                </div>
            ) : (
                <div className="border border-rule">
                    {profiles.map((p) => (
                        <div
                            key={p.id}
                            className="border-b border-rule p-5 last:border-b-0"
                        >
                            <div className="mb-2 flex items-center justify-between">
                                <div>
                                    <div className="text-item-title">
                                        {p.userName}
                                    </div>
                                    <div className="text-caption">
                                        {p.userEmail}
                                    </div>
                                </div>
                                <span className="font-mono text-lg text-ink-muted">
                                    {p.submittedAt &&
                                        new Date(
                                            p.submittedAt,
                                        ).toLocaleDateString('fr-FR')}
                                </span>
                            </div>

                            <div className="mb-3 flex flex-wrap gap-3">
                                {p.documents.map((d) => (
                                    <a
                                        key={d.id}
                                        href={d.downloadUrl}
                                        className="flex items-center gap-1.5 border border-rule px-2 py-1 font-mono text-lg text-action hover:border-action"
                                    >
                                        <RiFileTextLine size={13} /> {d.name}
                                    </a>
                                ))}
                            </div>

                            <div className="flex gap-2">
                                <Button
                                    onClick={() => approve(p.id)}
                                    variant="secondary"
                                    size="sm"
                                >
                                    Approuver
                                </Button>
                                <Button
                                    onClick={() =>
                                        setRejecting(
                                            rejecting === p.id ? null : p.id,
                                        )
                                    }
                                    variant="outline"
                                    size="sm"
                                    className="text-ink-muted hover:border-action hover:text-action"
                                >
                                    Rejeter
                                </Button>
                            </div>

                            {rejecting === p.id && (
                                <RejectForm
                                    id={p.id}
                                    onDone={() => setRejecting(null)}
                                />
                            )}
                        </div>
                    ))}
                </div>
            )}
        </AdminLayout>
    );
}
