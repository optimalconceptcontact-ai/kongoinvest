import { Head, router } from '@inertiajs/react';
import { RiFileTextLine } from '@remixicon/react';
import { useState } from 'react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Button } from '@/components/Ui/Button';
import { Input } from '@/components/Ui/Input';

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
        router.post(`/admin/kyc/${id}/reject`, { reason }, { onSuccess: onDone });
    }

    return (
        <div className="mt-2 flex gap-2">
            <Input
                size="sm"
                className="flex-1 text-xs"
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
                <div className="border border-dashed border-slate-400 py-16 text-center text-body">
                    Aucun dossier en attente de vérification.
                </div>
            ) : (
                <div className="border border-slate-300">
                    {profiles.map((p) => (
                        <div key={p.id} className="border-b border-slate-300 p-5 last:border-b-0">
                            <div className="mb-2 flex items-center justify-between">
                                <div>
                                    <div className="text-item-title">{p.userName}</div>
                                    <div className="text-caption">{p.userEmail}</div>
                                </div>
                                <span className="font-mono text-[11px] text-slate-400">
                                    {p.submittedAt && new Date(p.submittedAt).toLocaleDateString('fr-FR')}
                                </span>
                            </div>

                            <div className="mb-3 flex flex-wrap gap-3">
                                {p.documents.map((d) => (
                                    <a key={d.id} href={d.downloadUrl} className="flex items-center gap-1.5 border border-slate-300 px-2 py-1 font-mono text-xs text-blue-600 hover:border-blue-600">
                                        <RiFileTextLine size={13} /> {d.name}
                                    </a>
                                ))}
                            </div>

                            <div className="flex gap-2">
                                <Button onClick={() => approve(p.id)} variant="secondary" size="sm">
                                    Approuver
                                </Button>
                                <Button
                                    onClick={() => setRejecting(rejecting === p.id ? null : p.id)}
                                    variant="outline"
                                    size="sm"
                                    className="text-slate-500 hover:border-orange-600 hover:text-orange-600"
                                >
                                    Rejeter
                                </Button>
                            </div>

                            {rejecting === p.id && <RejectForm id={p.id} onDone={() => setRejecting(null)} />}
                        </div>
                    ))}
                </div>
            )}
        </AdminLayout>
    );
}
