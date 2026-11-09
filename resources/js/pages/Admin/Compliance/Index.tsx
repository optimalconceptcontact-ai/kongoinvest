import { Head, Link } from '@inertiajs/react';
import {
    RiAlertLine,
    RiArrowRightLine,
    RiFilterLine,
    RiFolderShield2Line,
    RiShieldCheckLine,
} from '@remixicon/react';
import { AdminLayout } from '@/layouts/AdminLayout';

type CaseItem = {
    id: number;
    caseNumber: string;
    userName: string;
    userEmail: string | null;
    subjectType: string;
    status: string;
    statusLabel: string;
    riskLevel: string;
    riskLevelLabel: string;
    riskScore: number;
    openAlertsCount: number;
    submittedAt: string | null;
    updatedAt: string;
};

type Props = {
    cases: {
        data: CaseItem[];
        links: any[];
    };
    openAlertsTotal: number;
    statusFilter: string | null;
};

const riskColors: Record<string, string> = {
    low: 'text-action bg-action/10 border-action/20',
    medium: 'text-ink bg-surface-2 border-rule',
    high: 'text-signal bg-signal/10 border-signal/30',
    critical: 'text-signal bg-signal/20 border-signal font-bold',
};

export default function Index({ cases, openAlertsTotal, statusFilter }: Props) {
    return (
        <AdminLayout title="Console de Conformité & AML">
            <Head title="Admin — Conformité & AML" />

            <div className="flex flex-wrap items-center justify-between gap-4 mb-8">
                <div>
                    <h1 className="text-xl font-bold text-ink flex items-center gap-2">
                        <RiFolderShield2Line size={24} className="text-action" />
                        Dossiers de Conformité & Surveillance AML
                    </h1>
                    <p className="text-sm text-ink-muted mt-1">
                        Gestion centralisée des vérifications KYC/KYB, scoring de risque, gestion des alertes et conformité bancaire.
                    </p>
                </div>

                <div className="flex items-center gap-3">
                    <div className="border border-rule bg-surface px-4 py-2 text-sm flex items-center gap-2">
                        <RiAlertLine size={18} className={openAlertsTotal > 0 ? 'text-signal' : 'text-ink-muted'} />
                        <span className="font-semibold text-ink">{openAlertsTotal}</span>
                        <span className="text-ink-muted">Alerte(s) ouverte(s)</span>
                    </div>
                </div>
            </div>

            {/* Filters */}
            <div className="flex items-center gap-2 mb-6 overflow-x-auto pb-2 text-xs">
                <span className="text-ink-muted uppercase font-mono mr-2 flex items-center gap-1">
                    <RiFilterLine size={14} /> Filtre statut :
                </span>
                <Link
                    href="/admin/compliance"
                    className={`px-3 py-1.5 border transition-colors ${
                        !statusFilter ? 'bg-ink text-surface border-ink' : 'bg-surface text-ink border-rule hover:bg-surface-2'
                    }`}
                >
                    Tous les dossiers
                </Link>
                <Link
                    href="/admin/compliance?status=manual_review"
                    className={`px-3 py-1.5 border transition-colors ${
                        statusFilter === 'manual_review' ? 'bg-ink text-surface border-ink' : 'bg-surface text-ink border-rule hover:bg-surface-2'
                    }`}
                >
                    Examen Manuel
                </Link>
                <Link
                    href="/admin/compliance?status=enhanced_due_diligence"
                    className={`px-3 py-1.5 border transition-colors ${
                        statusFilter === 'enhanced_due_diligence' ? 'bg-ink text-surface border-ink' : 'bg-surface text-ink border-rule hover:bg-surface-2'
                    }`}
                >
                    Vigilance Renforcée (EDD)
                </Link>
                <Link
                    href="/admin/compliance?status=blocked"
                    className={`px-3 py-1.5 border transition-colors ${
                        statusFilter === 'blocked' ? 'bg-ink text-surface border-ink' : 'bg-surface text-ink border-rule hover:bg-surface-2'
                    }`}
                >
                    Dossiers Bloqués
                </Link>
            </div>

            {/* Cases Table */}
            <div className="border border-rule bg-surface overflow-hidden">
                {cases.data.length === 0 ? (
                    <div className="p-8 text-center text-sm text-ink-muted">
                        Aucun dossier de conformité ne correspond aux critères sélectionnés.
                    </div>
                ) : (
                    <table className="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr className="border-b border-rule bg-surface-2 text-xs uppercase text-ink-muted font-semibold">
                                <th className="p-3">Dossier / Réf</th>
                                <th className="p-3">Sujet / Utilisateur</th>
                                <th className="p-3">Type</th>
                                <th className="p-3">Niveau de Risque</th>
                                <th className="p-3">Statut de Conformité</th>
                                <th className="p-3">Alertes</th>
                                <th className="p-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-rule">
                            {cases.data.map((c) => (
                                <tr key={c.id} className="hover:bg-surface-2 transition-colors">
                                    <td className="p-3 font-mono text-xs font-bold text-ink">
                                        {c.caseNumber}
                                    </td>
                                    <td className="p-3">
                                        <div className="font-semibold text-ink">{c.userName}</div>
                                        <div className="text-xs text-ink-muted">{c.userEmail}</div>
                                    </td>
                                    <td className="p-3 text-xs uppercase font-mono text-ink-muted">
                                        {c.subjectType}
                                    </td>
                                    <td className="p-3">
                                        <span className={`px-2 py-0.5 text-xs border uppercase font-mono ${riskColors[c.riskLevel]}`}>
                                            {c.riskLevelLabel} ({c.riskScore}/100)
                                        </span>
                                    </td>
                                    <td className="p-3">
                                        <span className="text-xs px-2 py-0.5 border border-rule bg-surface-2 text-ink">
                                            {c.statusLabel}
                                        </span>
                                    </td>
                                    <td className="p-3">
                                        {c.openAlertsCount > 0 ? (
                                            <span className="text-xs px-2 py-0.5 bg-signal/20 text-signal border border-signal/30 font-bold">
                                                {c.openAlertsCount} alerte(s)
                                            </span>
                                        ) : (
                                            <span className="text-xs text-ink-muted">0 alerte</span>
                                        )}
                                    </td>
                                    <td className="p-3 text-right">
                                        <Link
                                            href={`/admin/compliance/cases/${c.id}`}
                                            className="inline-flex items-center gap-1 text-xs font-semibold text-action hover:underline"
                                        >
                                            Examiner <RiArrowRightLine size={14} />
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </AdminLayout>
    );
}
