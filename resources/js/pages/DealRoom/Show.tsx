import { Head, Link as InertiaLink, router } from '@inertiajs/react';
import { ConfirmModal } from '@/components/ConfirmModal';
import { RiSendPlaneLine, RiFileTextLine, RiUploadLine, RiSparklingLine } from '@remixicon/react';
import { FormEvent, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { Button } from '@/components/Ui/Button';
import { Checkbox } from '@/components/Ui/Checkbox';
import { Input } from '@/components/Ui/Input';
import { Link } from '@/components/Ui/Link';
import { Select } from '@/components/Ui/Select';
import { Textarea } from '@/components/Ui/Textarea';
import { fundingTypeLabel } from '@/lib/fundingTypes';

type Message = { id: number; body: string; senderId: number; senderName: string; createdAt: string };

type DealDocument = {
    id: number;
    title: string;
    visibility: 'private' | 'shared' | 'requested' | 'rejected' | 'expired';
    ownerId: number;
    ownerName: string;
    requestedByUserId: number | null;
    downloadUrl: string | null;
};

type DealContract = {
    id: number;
    status: string;
    investmentType: string;
    amountMinor: number;
    currency: string;
    terms: { conditions?: string; deadline?: string } | null;
    entrepreneurAcceptedAt: string | null;
    investorAcceptedAt: string | null;
    latestVersionContent: { disclaimer: string } | null;
};

type DealEscrow = {
    id: number;
    status: string;
    amountMinor: number;
    currency: string;
    fundedAt: string | null;
    releasedAt: string | null;
    refundedAt: string | null;
    openDispute: { id: number; status: string } | null;
};

type DealRoomData = {
    id: number;
    projectTitle: string;
    projectSlug: string;
    projectInvestmentTypes: string[];
    entrepreneurName: string;
    investorName: string;
    status: string;
    messages: Message[];
    documents: DealDocument[];
    contracts: DealContract[];
    escrow: DealEscrow | null;
};

type SharedAuth = { auth: { user: { id: number } | null } };

function DocumentsPanel({ dealRoomId, documents, viewerId }: { dealRoomId: number; documents: DealDocument[]; viewerId: number | undefined }) {
    const [title, setTitle] = useState('');
    const [file, setFile] = useState<File | null>(null);
    const [shareNow, setShareNow] = useState(false);
    const [uploading, setUploading] = useState(false);

    function submit(e: FormEvent) {
        e.preventDefault();
        if (!file) return;
        setUploading(true);
        router.post(
            `/deal-rooms/${dealRoomId}/documents`,
            { title, file, share_immediately: shareNow },
            {
                forceFormData: true,
                onFinish: () => setUploading(false),
                onSuccess: () => { setTitle(''); setFile(null); setShareNow(false); },
            },
        );
    }

    return (
        <div className="border border-slate-200 p-5">
            <h2 className="mb-4 text-section-label">Documents</h2>

            {documents.length === 0 ? (
                <p className="mb-4 text-sm text-slate-400">Aucun document partagé pour l'instant.</p>
            ) : (
                <div className="mb-4 flex flex-col gap-2">
                    {documents.map((d) => (
                        <div key={d.id} className="flex items-center justify-between border-b border-slate-100 py-2 text-sm last:border-b-0">
                            <div className="flex items-center gap-2">
                                <RiFileTextLine size={16} className="text-slate-400" />
                                <span>{d.title}</span>
                                <span className="font-mono text-[10px] uppercase text-slate-400">{d.visibility}</span>
                            </div>
                            <div className="flex gap-2">
                                {d.downloadUrl && (d.visibility === 'shared' || d.ownerId === viewerId) && (
                                    <a href={d.downloadUrl} className="font-mono text-xs text-blue-600 hover:text-blue-700">Télécharger</a>
                                )}
                                {d.visibility === 'private' && d.ownerId !== viewerId && (
                                    <button onClick={() => router.post(`/documents/${d.id}/request`, {}, { preserveScroll: true })} className="font-mono text-xs text-orange-600 hover:text-orange-700">
                                        Demander l'accès
                                    </button>
                                )}
                                {d.visibility === 'requested' && d.ownerId === viewerId && (
                                    <>
                                        <button onClick={() => router.post(`/documents/${d.id}/approve`, {}, { preserveScroll: true })} className="font-mono text-xs text-blue-600 hover:text-blue-700">Partager</button>
                                        <button onClick={() => router.post(`/documents/${d.id}/reject`, {}, { preserveScroll: true })} className="font-mono text-xs text-slate-400 hover:text-orange-600">Refuser</button>
                                    </>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            <form onSubmit={submit} className="flex flex-wrap items-end gap-2 border-t border-slate-100 pt-4">
                <Input value={title} onChange={(e) => setTitle(e.target.value)} placeholder="Titre du document" size="sm" className="flex-1" />
                <input type="file" onChange={(e) => setFile(e.target.files?.[0] ?? null)} className="text-xs" />
                <Checkbox
                    label="Partager immédiatement"
                    checked={shareNow}
                    onChange={(e) => setShareNow(e.target.checked)}
                    size="sm"
                    className="gap-1 text-slate-600"
                />
                <Button type="submit" disabled={uploading || !file || !title} size="sm" className="flex items-center gap-1">
                    <RiUploadLine size={14} /> Ajouter
                </Button>
            </form>
        </div>
    );
}

function ContractPanel({
    dealRoomId,
    contracts,
    viewerRole,
    investmentTypes,
    hasEscrow,
}: {
    dealRoomId: number;
    contracts: DealContract[];
    viewerRole: 'entrepreneur' | 'investor';
    investmentTypes: string[];
    hasEscrow: boolean;
}) {
    const [showForm, setShowForm] = useState(false);
    const latest = contracts[0];
    const canGenerate = !latest || latest.status === 'rejected';

    return (
        <div className="border border-slate-200 p-5">
            <h2 className="mb-4 text-section-label">Contrat</h2>

            {latest && latest.latestVersionContent && (
                <div className="mb-3 border border-orange-200 bg-orange-50 px-3 py-2 text-[11px] text-orange-700">
                    {latest.latestVersionContent.disclaimer}
                </div>
            )}

            {latest ? (
                <div className="mb-4 text-sm">
                    <div className="mb-2 flex items-center justify-between">
                        <span className="font-mono text-xs uppercase text-slate-500">{latest.status}</span>
                        <span className="font-mono text-sm font-bold text-slate-900">
                            {(latest.amountMinor / 100).toLocaleString('fr-FR')} {latest.currency}
                        </span>
                    </div>
                    <div className="mb-3 text-caption">Type : {fundingTypeLabel(latest.investmentType)}</div>
                    {latest.terms?.conditions && (
                        <details className="mb-3">
                            <summary className="cursor-pointer font-mono text-[11px] uppercase text-blue-600 hover:text-blue-700">
                                Voir les termes détaillés (généré par IA)
                            </summary>
                            <pre className="mt-2 max-h-64 overflow-y-auto whitespace-pre-wrap border border-slate-100 bg-slate-50 p-3 text-xs text-slate-700">
                                {latest.terms.conditions}
                            </pre>
                        </details>
                    )}
                    <div className="mb-3 flex gap-4 font-mono text-[11px] text-slate-400">
                        <span>Entrepreneur : {latest.entrepreneurAcceptedAt ? '✓ accepté' : 'en attente'}</span>
                        <span>Investisseur : {latest.investorAcceptedAt ? '✓ accepté' : 'en attente'}</span>
                    </div>
                    {latest.status !== 'accepted' && latest.status !== 'rejected' && (
                        <div className="flex gap-2">
                            <Button variant="secondary" size="sm" onClick={() => router.post(`/contracts/${latest.id}/accept`, {}, { preserveScroll: true })}>
                                Accepter
                            </Button>
                            <button onClick={() => router.post(`/contracts/${latest.id}/reject`, {}, { preserveScroll: true })} className="border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-500 hover:border-orange-600 hover:text-orange-600">
                                Refuser
                            </button>
                        </div>
                    )}
                    {latest.status === 'accepted' && !hasEscrow && (
                        <Button variant="cta" size="sm" onClick={() => router.post(`/contracts/${latest.id}/escrow`)}>
                            Créer le séquestre (Escrow)
                        </Button>
                    )}
                </div>
            ) : (
                <p className="mb-4 text-sm text-slate-400">Aucun contrat généré pour l'instant.</p>
            )}

            {canGenerate && viewerRole === 'investor' && (
                showForm ? (
                    <GenerateContractAiForm dealRoomId={dealRoomId} investmentTypes={investmentTypes} onDone={() => setShowForm(false)} />
                ) : (
                    <Button variant="outline" size="sm" onClick={() => setShowForm(true)} className="flex items-center gap-1.5">
                        <RiSparklingLine size={14} /> Générer le contrat par IA
                    </Button>
                )
            )}
            {canGenerate && viewerRole === 'entrepreneur' && (
                <p className="text-caption">Seul l'investisseur peut demander la génération du contrat.</p>
            )}
        </div>
    );
}

/**
 * No more manual amount/currency/conditions entry — the investor only
 * picks which of the entrepreneur's 3 declared financing types they
 * want; the AI drafts everything else (amount, obligations, clauses)
 * from the Deal Room conversation (spec: contract generation is
 * investor-only and AI-driven).
 */
function GenerateContractAiForm({ dealRoomId, investmentTypes, onDone }: { dealRoomId: number; investmentTypes: string[]; onDone: () => void }) {
    const [investmentType, setInvestmentType] = useState(investmentTypes[0] ?? '');
    const [sending, setSending] = useState(false);

    function submit(e: FormEvent) {
        e.preventDefault();
        setSending(true);
        router.post(
            `/deal-rooms/${dealRoomId}/contracts`,
            { investment_type: investmentType },
            { onFinish: () => setSending(false), onSuccess: onDone },
        );
    }

    return (
        <form onSubmit={submit} className="flex flex-col gap-2 border-t border-slate-100 pt-4">
            <Select value={investmentType} onChange={(e) => setInvestmentType(e.target.value)} size="sm">
                {investmentTypes.map((t) => (
                    <option key={t} value={t}>{fundingTypeLabel(t)}</option>
                ))}
            </Select>
            <p className="text-caption">
                L'IA rédige les termes à partir de ce qui a été discuté et accepté dans la conversation ci-dessus.
            </p>
            <Button type="submit" disabled={sending || !investmentType} size="sm" className="flex items-center gap-1.5 self-start">
                <RiSparklingLine size={14} /> {sending ? 'Génération en cours...' : 'Générer'}
            </Button>
        </form>
    );
}

const escrowStatusLabels: Record<string, string> = {
    pending: 'En attente de financement',
    funded: 'Financé',
    held: 'Retenu en séquestre',
    awaiting_release: 'Libération en cours',
    released: 'Libéré',
    refund_requested: 'Remboursement demandé',
    refunded: 'Remboursé',
    cancelled: 'Annulé',
    disputed: 'En litige',
};

function EscrowPanel({ escrow, viewerRole }: { escrow: DealEscrow; viewerRole: 'entrepreneur' | 'investor' }) {
    const [disputing, setDisputing] = useState(false);
    const [reason, setReason] = useState('');
    const [confirmingRelease, setConfirmingRelease] = useState(false);
    const [confirmingRefund, setConfirmingRefund] = useState(false);
    const [actionPending, setActionPending] = useState(false);

    function submitDispute(e: FormEvent) {
        e.preventDefault();
        router.post(`/escrow/${escrow.id}/dispute`, { reason }, { onSuccess: () => setDisputing(false) });
    }

    function confirmRelease() {
        setActionPending(true);
        router.post(`/escrow/${escrow.id}/release`, {}, {
            preserveScroll: true,
            onFinish: () => { setActionPending(false); setConfirmingRelease(false); },
        });
    }

    function confirmRefund() {
        setActionPending(true);
        router.post(`/escrow/${escrow.id}/refund`, {}, {
            preserveScroll: true,
            onFinish: () => { setActionPending(false); setConfirmingRefund(false); },
        });
    }

    return (
        <div className="border border-slate-200 p-5">
            <h2 className="mb-4 text-section-label">Séquestre (Escrow)</h2>

            <div className="mb-4 flex items-center justify-between">
                <span className="font-mono text-xs uppercase text-slate-500">{escrowStatusLabels[escrow.status] ?? escrow.status}</span>
                <span className="font-mono text-sm font-bold text-slate-900">
                    {(escrow.amountMinor / 100).toLocaleString('fr-FR')} {escrow.currency}
                </span>
            </div>

            {escrow.openDispute && (
                <div className="mb-4 border border-orange-200 bg-orange-50 px-3 py-2 text-xs text-orange-700">
                    Litige ouvert — en cours d'examen par l'équipe Kanda.
                </div>
            )}

            {escrow.status === 'pending' && (
                <Link href={`/escrow/${escrow.id}/checkout`} variant="cta" size="sm" className="inline-block">
                    Financer le séquestre
                </Link>
            )}

            {escrow.status === 'held' && viewerRole === 'investor' && (
                <>
                <div className="flex flex-wrap gap-2">
                    <Button variant="secondary" size="sm" onClick={() => setConfirmingRelease(true)}>
                        Libérer les fonds
                    </Button>
                    <button
                        onClick={() => setConfirmingRefund(true)}
                        className="border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-500 hover:border-orange-600 hover:text-orange-600"
                    >
                        Demander un remboursement
                    </button>
                    <button onClick={() => setDisputing((v) => !v)} className="border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-500 hover:border-slate-900">
                        Ouvrir un litige
                    </button>
                </div>

                <ConfirmModal
                    open={confirmingRelease}
                    onClose={() => setConfirmingRelease(false)}
                    onConfirm={confirmRelease}
                    processing={actionPending}
                    title="Libérer les fonds"
                    description="Vous confirmez avoir reçu ce qui était convenu et autorisez la libération des fonds séquestrés vers l'entrepreneur. Cette action est irréversible."
                    confirmLabel="Confirmer la libération"
                />
                <ConfirmModal
                    open={confirmingRefund}
                    onClose={() => setConfirmingRefund(false)}
                    onConfirm={confirmRefund}
                    processing={actionPending}
                    title="Demander un remboursement"
                    description="Le fournisseur de paiement sera sollicité pour rembourser les fonds séquestrés sur votre moyen de paiement d'origine."
                    confirmLabel="Confirmer le remboursement"
                    danger
                />
                </>
            )}

            {escrow.status === 'held' && viewerRole === 'entrepreneur' && (
                <button onClick={() => setDisputing((v) => !v)} className="border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-500 hover:border-slate-900">
                    Ouvrir un litige
                </button>
            )}

            {disputing && (
                <form onSubmit={submitDispute} className="mt-3 flex flex-col gap-2 border-t border-slate-100 pt-3">
                    <Textarea
                        value={reason}
                        onChange={(e) => setReason(e.target.value)}
                        placeholder="Décrivez le problème..."
                        rows={2}
                        size="sm"
                    />
                    <Button type="submit" disabled={!reason.trim()} variant="destructive" size="sm" className="self-start">
                        Soumettre le litige
                    </Button>
                </form>
            )}

            {(escrow.status === 'released' || escrow.status === 'refunded') && (
                <p className="text-caption">
                    {escrow.status === 'released' ? `Libéré le ${new Date(escrow.releasedAt!).toLocaleDateString('fr-FR')}` : `Remboursé le ${new Date(escrow.refundedAt!).toLocaleDateString('fr-FR')}`}
                </p>
            )}
        </div>
    );
}

export default function Show({ dealRoom, viewerRole }: { dealRoom: DealRoomData; viewerRole: 'entrepreneur' | 'investor' }) {
    const { props } = usePage<SharedAuth>();
    const [body, setBody] = useState('');
    const [sending, setSending] = useState(false);
    const backHref = viewerRole === 'entrepreneur' ? '/entrepreneur/connections' : '/investor/connections';

    function submit(e: FormEvent) {
        e.preventDefault();
        if (!body.trim()) return;
        setSending(true);
        router.post(
            `/deal-rooms/${dealRoom.id}/messages`,
            { body },
            { preserveScroll: true, onSuccess: () => setBody(''), onFinish: () => setSending(false) },
        );
    }

    return (
        <div className="mx-auto max-w-3xl px-6 py-10">
            <Head title={`Deal Room — ${dealRoom.projectTitle}`} />

            <InertiaLink href={backHref} className="mb-4 inline-block font-mono text-xs text-slate-500 hover:text-slate-900">
                ← Retour
            </InertiaLink>

            <div className="mb-6 border-b border-slate-200 pb-6">
                <p className="text-eyebrow">Deal Room</p>
                <h1 className="mt-1 mb-2 text-page-title">
                    <InertiaLink href={`/projects/${dealRoom.projectSlug}`} className="hover:text-blue-700">{dealRoom.projectTitle}</InertiaLink>
                </h1>
                <p className="text-body">{dealRoom.entrepreneurName} × {dealRoom.investorName}</p>
            </div>

            <div className="mb-6 flex min-h-[240px] flex-col gap-3 border border-slate-200 p-5">
                {dealRoom.messages.length === 0 ? (
                    <p className="text-sm text-slate-400">Aucun message pour l'instant — lancez la conversation.</p>
                ) : (
                    dealRoom.messages.map((m) => {
                        const isMine = m.senderId === props.auth.user?.id;
                        return (
                            <div key={m.id} className={`flex flex-col ${isMine ? 'items-end' : 'items-start'}`}>
                                <div className={`max-w-[75%] px-3 py-2 text-sm ${isMine ? 'bg-slate-900 text-white' : 'border border-slate-200 text-slate-700'}`}>
                                    {m.body}
                                </div>
                                <div className="mt-1 font-mono text-[10px] text-slate-400">
                                    {m.senderName} · {new Date(m.createdAt).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}
                                </div>
                            </div>
                        );
                    })
                )}
            </div>

            <form onSubmit={submit} className="mb-8 flex gap-2">
                <Input
                    value={body}
                    onChange={(e) => setBody(e.target.value)}
                    placeholder="Écrire un message..."
                    className="flex-1 py-2.5"
                />
                <Button type="submit" disabled={sending || !body.trim()} className="flex items-center gap-1.5 py-2.5">
                    <RiSendPlaneLine size={16} />
                </Button>
            </form>

            <div className="flex flex-col gap-6">
                <DocumentsPanel dealRoomId={dealRoom.id} documents={dealRoom.documents} viewerId={props.auth.user?.id} />
                <ContractPanel
                    dealRoomId={dealRoom.id}
                    contracts={dealRoom.contracts}
                    viewerRole={viewerRole}
                    investmentTypes={dealRoom.projectInvestmentTypes}
                    hasEscrow={!!dealRoom.escrow}
                />
                {dealRoom.escrow && <EscrowPanel escrow={dealRoom.escrow} viewerRole={viewerRole} />}
            </div>
        </div>
    );
}
