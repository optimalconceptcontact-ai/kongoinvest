import { Head } from '@inertiajs/react';
import { InvestorLayout } from '@/layouts/InvestorLayout';
import { Link } from '@/components/Ui/Link';

type ContractRow = {
    id: number;
    dealRoomId: number;
    projectTitle: string;
    otherPartyName: string;
    status: string;
    statusLabel: string;
    investmentTypeLabel: string;
    amountMinor: number;
    currency: string;
    createdAt: string;
};

const statusStyles: Record<string, string> = {
    draft: 'bg-slate-100 text-slate-400',
    generated: 'bg-blue-50 text-blue-700',
    sent: 'bg-blue-50 text-blue-700',
    viewed: 'bg-blue-50 text-blue-700',
    accepted: 'bg-emerald-50 text-emerald-700',
    rejected: 'bg-slate-100 text-slate-400',
    expired: 'bg-slate-100 text-slate-400',
    cancelled: 'bg-slate-100 text-slate-400',
};

function formatAmount(minor: number, currency: string) {
    return `${(minor / 100).toLocaleString('fr-FR')} ${currency}`;
}

export default function Index({ contracts }: { contracts: ContractRow[] }) {
    return (
        <InvestorLayout title="Contrats">
            <Head title="Contrats" />

            {contracts.length === 0 ? (
                <div className="border border-dashed border-slate-400 py-16 text-center text-body">
                    Aucun contrat pour l'instant — les contrats sont générés depuis une Deal Room.
                </div>
            ) : (
                <div className="border border-slate-200">
                    {contracts.map((c) => (
                        <div key={c.id} className="flex items-center justify-between border-b border-slate-200 p-5 last:border-b-0">
                            <div>
                                <div className="mb-1 flex items-center gap-2">
                                    <span className={`px-2 py-0.5 font-mono text-[11px] uppercase ${statusStyles[c.status] ?? 'bg-slate-100 text-slate-400'}`}>
                                        {c.statusLabel}
                                    </span>
                                    <span className="font-mono text-[11px] text-slate-400">
                                        {new Date(c.createdAt).toLocaleDateString('fr-FR')}
                                    </span>
                                </div>
                                <div className="text-item-title">{c.projectTitle}</div>
                                <p className="text-caption">
                                    {c.otherPartyName} · {c.investmentTypeLabel} · {formatAmount(c.amountMinor, c.currency)}
                                </p>
                            </div>
                            <Link href={`/deal-rooms/${c.dealRoomId}`} variant="secondary" size="sm" className="py-2">
                                Ouvrir la Deal Room
                            </Link>
                        </div>
                    ))}
                </div>
            )}
        </InvestorLayout>
    );
}
