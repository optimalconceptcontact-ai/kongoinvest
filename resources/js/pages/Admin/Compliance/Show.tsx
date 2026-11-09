import { Head, router, useForm } from '@inertiajs/react';
import {
    RiAlertLine,
    RiBankLine,
    RiCheckboxCircleLine,
    RiFileLockLine,
    RiFileShield2Line,
    RiShieldCheckLine,
    RiTimeLine,
} from '@remixicon/react';
import { useState, type FormEvent } from 'react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { BackLink } from '@/components/ui/BackLink';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/ui/Field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/Label';

type Props = {
    case: {
        id: number;
        caseNumber: string;
        userName: string | null;
        userEmail: string | null;
        subjectType: string;
        status: string;
        statusLabel: string;
        riskLevel: string;
        riskLevelLabel: string;
        riskScore: number;
        notes: string | null;
        submittedAt: string | null;
        reviewedAt: string | null;
        isBlocked: boolean;
        suspensionOrBlockReason: string | null;
    };
    alerts: {
        id: number;
        type: string;
        typeLabel: string;
        severity: string;
        severityLabel: string;
        status: string;
        statusLabel: string;
        title: string;
        description: string;
        metadata: any;
        resolutionComment: string | null;
        resolvedAt: string | null;
    }[];
    riskAssessment: {
        overallScore: number;
        clientScore: number;
        uboScore: number;
        geoScore: number;
        sectorScore: number;
        productScore: number;
        sofScore: number;
        txScore: number;
        requiresEdd: boolean;
        mitigatingFactors: string | null;
        rationale: string | null;
    } | null;
    actions: {
        id: number;
        actor: string;
        actionType: string;
        comment: string | null;
        createdAt: string;
    }[];
    bankMatrix: Record<string, { actor: string; responsibilities: string[]; legal_basis: string }>;
};

export default function Show({ case: c, alerts, riskAssessment, actions, bankMatrix }: Props) {
    const [resolvingAlertId, setResolvingAlertId] = useState<number | null>(null);
    const [resolutionDecision, setResolutionDecision] = useState<'false_positive' | 'confirmed_risk'>('false_positive');
    const [resolutionComment, setResolutionComment] = useState('');

    const [showDosModal, setShowDosModal] = useState(false);
    const [dosNarrative, setDosNarrative] = useState('');
    const [selectedAlerts, setSelectedAlerts] = useState<number[]>([]);
    const [dosResult, setDosResult] = useState<any | null>(null);

    // Approval Form
    const approvalForm = useForm<{
        rationale: string;
        conditions: string;
    }>({
        rationale: 'Dossier complet et vérifié conforme aux exigences AML/CFT et OHADA.',
        conditions: '',
    });

    function submitApproval(e: FormEvent) {
        e.preventDefault();
        approvalForm.post(`/admin/compliance/cases/${c.id}/approve`);
    }

    function submitAlertResolution(alertId: number) {
        router.post(
            `/admin/compliance/alerts/${alertId}/resolve`,
            {
                decision: resolutionDecision,
                comment: resolutionComment,
            },
            {
                onSuccess: () => {
                    setResolvingAlertId(null);
                    setResolutionComment('');
                },
            }
        );
    }

    async function handlePrepareDos() {
        if (!dosNarrative || selectedAlerts.length === 0) return;

        try {
            const res = await fetch(`/admin/compliance/cases/${c.id}/dos`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as any)?.content ?? '',
                },
                body: JSON.stringify({
                    narrative: dosNarrative,
                    alert_ids: selectedAlerts,
                }),
            });
            const data = await res.json();
            setDosResult(data);
        } catch (e) {
            console.error(e);
        }
    }

    function transmitToBank() {
        router.post(`/admin/compliance/cases/${c.id}/transmit-bank`);
    }

    return (
        <AdminLayout title={`Dossier Conformité — ${c.caseNumber}`}>
            <Head title={`Admin — ${c.caseNumber}`} />
            <BackLink href="/admin/compliance" />

            <div className="flex flex-wrap items-center justify-between gap-4 mb-8">
                <div>
                    <span className="text-xs font-mono uppercase text-ink-muted">Dossier Conformité</span>
                    <h1 className="text-2xl font-bold text-ink mt-0.5">{c.caseNumber}</h1>
                    <p className="text-sm text-ink-muted">
                        Demandeur : {c.userName} ({c.userEmail}) — Sujet : {c.subjectType}
                    </p>
                </div>

                <div className="flex gap-3">
                    <Button onClick={transmitToBank} variant="outline" size="sm">
                        <RiBankLine size={16} /> Transmettre à la Banque Partenaire
                    </Button>
                    <Button onClick={() => setShowDosModal(true)} variant="destructive" size="sm">
                        <RiFileShield2Line size={16} /> Constituer Dossier Soupçon (DOS)
                    </Button>
                </div>
            </div>

            {/* Blocked Alert Banner if blocked */}
            {c.isBlocked && (
                <div className="mb-8 border border-signal bg-signal/15 p-4 text-ink flex items-start gap-3">
                    <RiAlertLine size={24} className="text-signal shrink-0 mt-0.5" />
                    <div>
                        <strong className="font-bold text-signal">DOSSIER BLOQUÉ PAR LA CONFORMITÉ</strong>
                        <p className="text-xs text-ink mt-1">
                            Motif : {c.suspensionOrBlockReason || 'Correspondance sur listes de sanctions ou anomalie critique.'}
                        </p>
                        <p className="text-xs text-ink-muted mt-0.5">
                            Toute opération financière, closing et accès aux flux sont suspendus jusqu'à résolution formelle.
                        </p>
                    </div>
                </div>
            )}

            {/* Risk Assessment Grid */}
            {riskAssessment && (
                <div className="mb-8 border border-rule bg-surface p-6 shadow-xs">
                    <div className="flex items-center justify-between mb-4 border-b border-rule pb-3">
                        <div>
                            <h2 className="text-base font-bold text-ink">Évaluation des Risques AML (Approche RBA)</h2>
                            <p className="text-xs text-ink-muted">
                                Matrice de risque multicritères conforme à la Loi n° 22/068 et Loi n° 25/048 (RDC).
                            </p>
                        </div>
                        <div className="text-right">
                            <span className="text-xs uppercase text-ink-muted">Score global</span>
                            <div className="text-xl font-mono font-bold text-action">
                                {riskAssessment.overallScore} / 100 ({c.riskLevelLabel})
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 text-center">
                        <div className="border border-rule bg-surface-2 p-2.5">
                            <span className="text-xs text-ink-muted">Client</span>
                            <div className="text-base font-bold text-ink mt-1">{riskAssessment.clientScore}/100</div>
                        </div>
                        <div className="border border-rule bg-surface-2 p-2.5">
                            <span className="text-xs text-ink-muted">UBO / Chaîne</span>
                            <div className="text-base font-bold text-ink mt-1">{riskAssessment.uboScore}/100</div>
                        </div>
                        <div className="border border-rule bg-surface-2 p-2.5">
                            <span className="text-xs text-ink-muted">Géographie</span>
                            <div className="text-base font-bold text-ink mt-1">{riskAssessment.geoScore}/100</div>
                        </div>
                        <div className="border border-rule bg-surface-2 p-2.5">
                            <span className="text-xs text-ink-muted">Secteur</span>
                            <div className="text-base font-bold text-ink mt-1">{riskAssessment.sectorScore}/100</div>
                        </div>
                        <div className="border border-rule bg-surface-2 p-2.5">
                            <span className="text-xs text-ink-muted">Canal</span>
                            <div className="text-base font-bold text-ink mt-1">{riskAssessment.productScore}/100</div>
                        </div>
                        <div className="border border-rule bg-surface-2 p-2.5">
                            <span className="text-xs text-ink-muted">Origine Fonds</span>
                            <div className="text-base font-bold text-ink mt-1">{riskAssessment.sofScore}/100</div>
                        </div>
                        <div className="border border-rule bg-surface-2 p-2.5">
                            <span className="text-xs text-ink-muted">Transactionnel</span>
                            <div className="text-base font-bold text-ink mt-1">{riskAssessment.txScore}/100</div>
                        </div>
                    </div>

                    {riskAssessment.mitigatingFactors && (
                        <div className="mt-4 p-3 bg-surface-2 border border-rule text-xs text-ink-muted">
                            <strong className="text-ink">Facteurs atténuants :</strong> {riskAssessment.mitigatingFactors}
                        </div>
                    )}
                </div>
            )}

            {/* AML Alerts Section */}
            <div className="mb-8 border border-rule bg-surface">
                <div className="p-4 border-b border-rule bg-surface-2 font-semibold text-sm flex items-center justify-between">
                    <span>Alertes & Correspondances ({alerts.length})</span>
                    <span className="text-xs text-ink-muted">Gestion humaine des faux positifs & correspondances</span>
                </div>

                {alerts.length === 0 ? (
                    <div className="p-6 text-center text-sm text-ink-muted">
                        Aucune alerte active pour ce dossier.
                    </div>
                ) : (
                    <div className="divide-y divide-rule text-sm">
                        {alerts.map((alert) => (
                            <div key={alert.id} className="p-4 flex flex-col gap-2">
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <div className="font-bold text-ink flex items-center gap-2">
                                            <RiAlertLine size={16} className={alert.severity === 'critical' ? 'text-signal' : 'text-action'} />
                                            {alert.title}
                                            <span className="text-xs uppercase font-mono px-2 py-0.5 bg-surface-2 border border-rule">
                                                {alert.severityLabel}
                                            </span>
                                        </div>
                                        <p className="text-xs text-ink-muted mt-1">{alert.description}</p>
                                    </div>
                                    <span className="text-xs font-mono uppercase bg-surface-2 px-2 py-1 border border-rule text-ink-muted">
                                        {alert.statusLabel}
                                    </span>
                                </div>

                                {alert.status === 'open' && (
                                    <div className="mt-2 pt-2 border-t border-rule flex items-center gap-2">
                                        {resolvingAlertId === alert.id ? (
                                            <div className="w-full space-y-2 bg-surface-2 p-3 border border-rule">
                                                <div className="flex gap-4 text-xs">
                                                    <label className="flex items-center gap-1 cursor-pointer">
                                                        <input
                                                            type="radio"
                                                            checked={resolutionDecision === 'false_positive'}
                                                            onChange={() => setResolutionDecision('false_positive')}
                                                        />
                                                        <span>Faux positif (Homonymie avérée)</span>
                                                    </label>
                                                    <label className="flex items-center gap-1 cursor-pointer">
                                                        <input
                                                            type="radio"
                                                            checked={resolutionDecision === 'confirmed_risk'}
                                                            onChange={() => setResolutionDecision('confirmed_risk')}
                                                        />
                                                        <span>Risque avéré</span>
                                                    </label>
                                                </div>
                                                <Input
                                                    value={resolutionComment}
                                                    onChange={(e) => setResolutionComment(e.target.value)}
                                                    placeholder="Motif de résolution..."
                                                    className="text-xs"
                                                />
                                                <div className="flex gap-2 justify-end">
                                                    <Button size="sm" variant="outline" onClick={() => setResolvingAlertId(null)}>
                                                        Annuler
                                                    </Button>
                                                    <Button size="sm" onClick={() => submitAlertResolution(alert.id)}>
                                                        Confirmer la décision
                                                    </Button>
                                                </div>
                                            </div>
                                        ) : (
                                            <Button size="sm" variant="outline" onClick={() => setResolvingAlertId(alert.id)}>
                                                Examiner & Traiter l'alerte
                                            </Button>
                                        )}
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </div>

            {/* Approval Decision Form */}
            <form onSubmit={submitApproval} className="mb-8 border border-rule p-6 bg-surface">
                <h3 className="font-semibold text-ink text-base mb-1">Décision Formelle du Responsable Conformité</h3>
                <p className="text-xs text-ink-muted mb-4">
                    Approbation officielle du dossier autorisant les opérations d'investissement.
                </p>

                <div className="space-y-4">
                    <Field error={!!approvalForm.errors.rationale}>
                        <Label>Motivation de la décision *</Label>
                        <Input
                            value={approvalForm.data.rationale}
                            onChange={(e) => approvalForm.setData('rationale', e.target.value)}
                        />
                    </Field>

                    <Field error={!!approvalForm.errors.conditions}>
                        <Label>Conditions suspensives éventuelles (le cas échéant)</Label>
                        <Input
                            value={approvalForm.data.conditions}
                            onChange={(e) => approvalForm.setData('conditions', e.target.value)}
                            placeholder="Ex: Justificatif fiscal complémentaire avant tout décaissement"
                        />
                    </Field>

                    <Button type="submit" disabled={approvalForm.processing || c.isBlocked} className="px-8 py-3">
                        <RiShieldCheckLine size={18} /> Valider & Approuver le dossier
                    </Button>
                </div>
            </form>

            {/* Audit Timeline */}
            <div className="border border-rule bg-surface p-4">
                <h3 className="font-semibold text-sm mb-3 flex items-center gap-2">
                    <RiTimeLine size={16} /> Journal des actions & horodatage légal (10 ans)
                </h3>
                <div className="space-y-2 text-xs divide-y divide-rule">
                    {actions.map((act) => (
                        <div key={act.id} className="pt-2 first:pt-0 flex items-center justify-between text-ink-muted">
                            <div>
                                <strong className="text-ink">{act.actor}</strong> : {act.actionType} {act.comment && `— ${act.comment}`}
                            </div>
                            <span className="font-mono">{new Date(act.createdAt).toLocaleString('fr-FR')}</span>
                        </div>
                    ))}
                </div>
            </div>

            {/* Modal: DOS Preparation */}
            {showDosModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-2xl border border-rule bg-surface p-6 shadow-lg max-h-[90vh] overflow-y-auto">
                        <h3 className="text-lg font-bold text-signal flex items-center gap-2 mb-1">
                            <RiFileLockLine size={22} /> Constitution du Dossier d'Analyse de Soupçon (DOS)
                        </h3>
                        <p className="text-xs text-ink-muted mb-4">
                            Strictement confidentiel. Aucune déclaration n'est transmise automatiquement sur la base d'un simple score.
                            Ce workflow prépare la synthèse pour l'assujetti légalement tenu de déclarer.
                        </p>

                        {!dosResult ? (
                            <div className="space-y-4">
                                <div>
                                    <Label>Sélectionner les alertes motivant le soupçon :</Label>
                                    <div className="space-y-2 mt-2">
                                        {alerts.map((al) => (
                                            <label key={al.id} className="flex items-center gap-2 text-xs cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    checked={selectedAlerts.includes(al.id)}
                                                    onChange={(e) => {
                                                        if (e.target.checked) {
                                                            setSelectedAlerts([...selectedAlerts, al.id]);
                                                        } else {
                                                            setSelectedAlerts(selectedAlerts.filter((id) => id !== al.id));
                                                        }
                                                    }}
                                                />
                                                <span className="text-ink font-medium">{al.title}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>

                                <div>
                                    <Label>Exposé factuel circonstancié du soupçon *</Label>
                                    <textarea
                                        value={dosNarrative}
                                        onChange={(e) => setDosNarrative(e.target.value)}
                                        rows={4}
                                        className="w-full border border-rule bg-surface p-2 text-sm text-ink"
                                        placeholder="Décrire les constatations objectives : flux inhabituels, origine injustifiée, incohérence..."
                                    />
                                </div>

                                <div className="p-3 bg-surface-2 border border-rule text-xs text-signal font-semibold">
                                    Rappel légal : Interdiction absolue de divulgation au client (Article 35 Loi n° 22/068 - Anti-tipping off).
                                </div>

                                <div className="flex justify-end gap-3 pt-4 border-t border-rule">
                                    <Button type="button" variant="outline" onClick={() => setShowDosModal(false)}>
                                        Fermer
                                    </Button>
                                    <Button onClick={handlePrepareDos} variant="destructive">
                                        Constituer le dossier d'analyse
                                    </Button>
                                </div>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                <div className="p-4 bg-action/10 border border-action/30 text-action text-sm font-semibold">
                                    Dossier d'analyse constitué avec succès : {dosResult.dos_reference}
                                </div>
                                <div className="text-xs text-ink-muted space-y-1">
                                    <div><strong>Horodatage :</strong> {dosResult.constituted_at}</div>
                                    <div><strong>Responsable conformité :</strong> {dosResult.officer_name}</div>
                                    <div><strong>Indicateurs :</strong> {dosResult.suspicious_indicators.join(', ')}</div>
                                    <div className="mt-2 text-signal"><strong>Règle de confidentialité :</strong> {dosResult.confidentiality_rule}</div>
                                </div>
                                <div className="flex justify-end pt-4 border-t border-rule">
                                    <Button onClick={() => setShowDosModal(false)}>Terminer</Button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
