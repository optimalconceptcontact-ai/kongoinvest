import { router } from '@inertiajs/react';
import { RiAlertLine, RiCloseLine, RiRefreshLine, RiSparklingLine } from '@remixicon/react';
import { useState } from 'react';
import { Button } from '@/components/Ui/Button';

export type AiAnalysisData = {
    id: number;
    status: 'pending' | 'completed' | 'failed';
    investmentScore: number | null;
    subScores: Record<string, number> | null;
    executiveSummary: string | null;
    businessModelAssessment: string | null;
    marketPotential: string | null;
    problemSolutionFit: string | null;
    competitiveAdvantage: string | null;
    teamAssessment: string | null;
    tractionAssessment: string | null;
    financialPotential: string | null;
    scalability: string | null;
    strengths: string[];
    weaknesses: string[];
    risks: string[];
    redFlags: string[];
    dueDiligenceQuestions: string[];
    fundingAssessment: string | null;
    recommendation: string | null;
    disclaimer: string | null;
    failureReason: string | null;
};

const subScoreLabels: Record<string, string> = {
    market: 'Marché', team: 'Équipe', traction: 'Traction', businessModel: 'Modèle économique',
    scalability: 'Scalabilité', risk: 'Risque', financialPotential: 'Potentiel financier',
};

function Section({ title, body }: { title: string; body: string | null }) {
    if (!body) return null;
    return (
        <div className="border-b border-slate-100 py-4">
            <h4 className="mb-1.5 text-xs font-bold uppercase tracking-wide text-slate-900">{title}</h4>
            <p className="text-sm leading-relaxed text-slate-600">{body}</p>
        </div>
    );
}

function ListSection({ title, items, tone = 'default' }: { title: string; items: string[]; tone?: 'default' | 'warning' }) {
    if (items.length === 0) return null;
    return (
        <div className="border-b border-slate-100 py-4">
            <h4 className="mb-2 text-xs font-bold uppercase tracking-wide text-slate-900">{title}</h4>
            <ul className="flex flex-col gap-1.5">
                {items.map((item, i) => (
                    <li key={i} className={`flex gap-2 text-sm ${tone === 'warning' ? 'text-orange-700' : 'text-slate-600'}`}>
                        <span className={tone === 'warning' ? 'text-orange-500' : 'text-blue-500'}>—</span>
                        {item}
                    </li>
                ))}
            </ul>
        </div>
    );
}

export function AIAnalysisSheet({
    open,
    onClose,
    analysis,
    projectId,
}: {
    open: boolean;
    onClose: () => void;
    analysis: AiAnalysisData | null;
    projectId: number;
}) {
    const [requesting, setRequesting] = useState(false);

    function requestAnalysis() {
        setRequesting(true);
        router.post(`/projects/${projectId}/ai-analysis`, {}, {
            preserveScroll: true,
            onFinish: () => setRequesting(false),
        });
    }

    if (!open) return null;

    return (
        <div className="fixed inset-0 z-[100] flex justify-end bg-slate-900/40" onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
            <div className="flex h-full w-full max-w-md flex-col bg-white sm:max-w-lg">
                <div className="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <div className="flex items-center gap-2">
                        <RiSparklingLine size={18} className="text-orange-500" />
                        <h2 className="text-section-label">Analyse IA</h2>
                    </div>
                    <button onClick={onClose} aria-label="Fermer" className="text-slate-400 hover:text-slate-900">
                        <RiCloseLine size={20} />
                    </button>
                </div>

                <div className="flex-1 overflow-y-auto px-6 py-4">
                    {!analysis && (
                        <div className="flex flex-col items-center gap-4 py-16 text-center">
                            <RiSparklingLine size={28} className="text-orange-300" />
                            <p className="text-body">Aucune analyse n'a encore été générée pour ce projet.</p>
                            <Button onClick={requestAnalysis} disabled={requesting} className="px-5 py-2.5">
                                {requesting ? 'Analyse en cours...' : 'Lancer l\u2019analyse IA'}
                            </Button>
                        </div>
                    )}

                    {analysis?.status === 'failed' && (
                        <div className="flex flex-col items-center gap-4 py-16 text-center">
                            <RiAlertLine size={28} className="text-orange-500" />
                            <p className="text-body">{analysis.failureReason ?? "L'analyse n'a pas pu être générée."}</p>
                            <Button
                                variant="outline"
                                onClick={requestAnalysis}
                                disabled={requesting}
                                className="flex items-center gap-1.5 text-xs"
                            >
                                <RiRefreshLine size={14} /> Réessayer
                            </Button>
                        </div>
                    )}

                    {analysis?.status === 'completed' && (
                        <>
                            <div className="mb-6 flex items-center justify-between border border-slate-200 bg-slate-50 p-4">
                                <div>
                                    <div className="font-mono text-[11px] uppercase text-slate-500">Investment Score</div>
                                    <div className="text-stat-lg">{analysis.investmentScore}<span className="text-base text-slate-400">/100</span></div>
                                </div>
                                <button onClick={requestAnalysis} disabled={requesting} className="flex items-center gap-1 font-mono text-[11px] text-blue-600 hover:text-blue-700 disabled:opacity-50">
                                    <RiRefreshLine size={12} /> Régénérer
                                </button>
                            </div>

                            {analysis.subScores && (
                                <div className="mb-6 flex flex-col gap-2">
                                    {Object.entries(analysis.subScores).map(([key, value]) => (
                                        <div key={key} className="flex items-center gap-3">
                                            <div className="w-32 flex-shrink-0 text-xs text-slate-600">{subScoreLabels[key] ?? key}</div>
                                            <div className="h-1.5 flex-1 bg-slate-100">
                                                <div className="h-full bg-blue-500" style={{ width: `${value}%` }} />
                                            </div>
                                            <div className="w-8 flex-shrink-0 text-right font-mono text-[11px] text-slate-500">{value}</div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            <Section title="Résumé exécutif" body={analysis.executiveSummary} />
                            <Section title="Modèle économique" body={analysis.businessModelAssessment} />
                            <Section title="Potentiel de marché" body={analysis.marketPotential} />
                            <Section title="Adéquation problème/solution" body={analysis.problemSolutionFit} />
                            <Section title="Avantage concurrentiel" body={analysis.competitiveAdvantage} />
                            <Section title="Équipe" body={analysis.teamAssessment} />
                            <Section title="Traction" body={analysis.tractionAssessment} />
                            <Section title="Potentiel financier" body={analysis.financialPotential} />
                            <Section title="Scalabilité" body={analysis.scalability} />
                            <ListSection title="Points forts" items={analysis.strengths} />
                            <ListSection title="Points faibles" items={analysis.weaknesses} />
                            <ListSection title="Risques" items={analysis.risks} tone="warning" />
                            <ListSection title="Signaux d'alerte" items={analysis.redFlags} tone="warning" />
                            <ListSection title="Questions de due diligence" items={analysis.dueDiligenceQuestions} />
                            <Section title="Évaluation du financement demandé" body={analysis.fundingAssessment} />
                            <Section title="Synthèse" body={analysis.recommendation} />
                        </>
                    )}
                </div>

                {analysis?.status === 'completed' && analysis.disclaimer && (
                    <div className="border-t border-slate-200 px-6 py-3 text-[11px] leading-relaxed text-slate-400">
                        {analysis.disclaimer}
                    </div>
                )}
            </div>
        </div>
    );
}
