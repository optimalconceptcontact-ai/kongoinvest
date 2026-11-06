import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/Textarea';

type DisputeRow = {
    id: number;
    reason: string;
    status: string;
    raisedByName: string;
    projectTitle: string;
    amountMinor: number;
    currency: string;
    createdAt: string;
};

function ResolveForm({ id, onDone }: { id: number; onDone: () => void }) {
    const [resolution, setResolution] = useState<
        'release' | 'refund' | 'reject'
    >('release');
    const [notes, setNotes] = useState('');

    function submit() {
        if (!notes.trim()) return;
        router.post(
            `/admin/disputes/${id}/resolve`,
            { resolution, notes },
            { onSuccess: onDone },
        );
    }

    return (
        <div className="mt-3 flex flex-col gap-2 border-t border-rule pt-3">
            <div className="flex gap-2">
                {(['release', 'refund', 'reject'] as const).map((r) => (
                    <button
                        key={r}
                        onClick={() => setResolution(r)}
                        className={`min-h-11 border px-3 py-1.5 text-btn ${resolution === r ? 'border-ink bg-ink text-paper' : 'border-rule-strong text-ink'}`}
                    >
                        {r === 'release'
                            ? 'Libérer les fonds'
                            : r === 'refund'
                              ? "Rembourser l'investisseur"
                              : 'Rejeter le litige'}
                    </button>
                ))}
            </div>
            <Textarea
                size="sm"
                className="text-lg"
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                placeholder="Notes de résolution (visibles aux deux parties)"
                rows={2}
            />
            <Button
                onClick={submit}
                variant="secondary"
                size="sm"
                className="self-start"
            >
                Confirmer la résolution
            </Button>
        </div>
    );
}

export default function Index({ disputes }: { disputes: DisputeRow[] }) {
    const [resolving, setResolving] = useState<number | null>(null);

    return (
        <AdminLayout title="Litiges">
            <Head title="Admin — Litiges" />

            {disputes.length === 0 ? (
                <div className="border border-rule-strong bg-paper p-6 text-body">
                    Aucun litige ouvert.
                </div>
            ) : (
                <div className="border border-rule">
                    {disputes.map((d) => (
                        <div
                            key={d.id}
                            className="border-b border-rule p-5 last:border-b-0"
                        >
                            <div className="mb-2 flex items-center justify-between">
                                <div>
                                    <div className="text-item-title">
                                        {d.projectTitle}
                                    </div>
                                    <div className="text-caption">
                                        Ouvert par {d.raisedByName}
                                    </div>
                                </div>
                                <div className="text-right">
                                    <div className="font-mono text-lg font-bold text-ink">
                                        {(d.amountMinor / 100).toLocaleString(
                                            'fr-FR',
                                        )}{' '}
                                        {d.currency}
                                    </div>
                                    <div className="font-mono text-lg text-ink-muted">
                                        {new Date(
                                            d.createdAt,
                                        ).toLocaleDateString('fr-FR')}
                                    </div>
                                </div>
                            </div>
                            <p className="mb-3 text-lg text-ink">{d.reason}</p>
                            {resolving === d.id ? (
                                <ResolveForm
                                    id={d.id}
                                    onDone={() => setResolving(null)}
                                />
                            ) : (
                                <Button
                                    onClick={() => setResolving(d.id)}
                                    variant="outline"
                                    size="sm"
                                >
                                    Résoudre
                                </Button>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </AdminLayout>
    );
}
