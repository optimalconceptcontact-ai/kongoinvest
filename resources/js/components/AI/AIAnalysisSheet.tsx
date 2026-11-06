import { router } from '@inertiajs/react';
import {
    RiAlertLine,
    RiCloseLine,
    RiRefreshLine,
    RiSparklingLine,
} from '@remixicon/react';
import { AnimatePresence, motion } from 'framer-motion';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { fadeIn, slideInRight } from '@/lib/animations';

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
    market: 'Marché',
    team: 'Équipe',
    traction: 'Traction',
    businessModel: 'Modèle économique',
    scalability: 'Scalabilité',
    risk: 'Risque',
    financialPotential: 'Potentiel financier',
};

function Section({ title, body }: { title: string; body: string | null }) {
    if (!body) {
        return null;
    }

    return (
        <div className="border-b border-rule py-4">
            <h4 className="mb-1.5 text-section-label">{title}</h4>
            <p className="text-xl leading-6 text-ink">{body}</p>
        </div>
    );
}

function ListSection({
    title,
    items,
    tone = 'default',
}: {
    title: string;
    items: string[];
    tone?: 'default' | 'warning';
}) {
    if (items.length === 0) {
        return null;
    }

    return (
        <div className="border-b border-rule py-4">
            <h4 className="mb-2 text-section-label">{title}</h4>
            <ul className="flex flex-col gap-1.5">
                {items.map((item, i) => (
                    <li
                        key={i}
                        className={`flex gap-2 text-xl leading-6 ${tone === 'warning' ? 'text-danger' : 'text-ink'}`}
                    >
                        <span
                            className={
                                tone === 'warning' ? 'text-danger' : 'text-ink'
                            }
                        >
                            —
                        </span>
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
        router.post(
            `/projects/${projectId}/ai-analysis`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setRequesting(false),
            },
        );
    }

    return (
        <AnimatePresence>
            {open && (
                <motion.div
                    variants={fadeIn}
                    initial="initial"
                    animate="animate"
                    exit="exit"
                    className="fixed inset-0 z-[100] flex justify-end bg-black/50"
                    onMouseDown={(e) =>
                        e.target === e.currentTarget && onClose()
                    }
                >
                    <motion.div
                        variants={slideInRight}
                        className="flex h-full w-full max-w-md flex-col bg-paper sm:max-w-lg"
                    >
                        <div className="flex items-center justify-between border-b border-rule px-6 py-4">
                            <div className="flex items-center gap-2">
                                <RiSparklingLine
                                    size={18}
                                    className="text-ink"
                                />
                                <h2 className="text-section-label">
                                    Analyse IA
                                </h2>
                            </div>
                            <button
                                onClick={onClose}
                                aria-label="Fermer"
                                className="text-ink-muted hover:text-ink"
                            >
                                <RiCloseLine size={20} />
                            </button>
                        </div>

                        <div className="flex-1 overflow-y-auto px-6 py-4">
                            {!analysis && (
                                <div className="flex flex-col items-center gap-4 py-16 text-center">
                                    <RiSparklingLine
                                        size={28}
                                        className="text-ink"
                                    />
                                    <p className="text-body">
                                        Aucune analyse n'a encore été générée
                                        pour ce projet.
                                    </p>
                                    <Button
                                        onClick={requestAnalysis}
                                        disabled={requesting}
                                        className="px-5 py-2.5"
                                    >
                                        {requesting
                                            ? 'Analyse en cours...'
                                            : 'Lancer l\u2019analyse IA'}
                                    </Button>
                                </div>
                            )}

                            {analysis?.status === 'failed' && (
                                <div className="flex flex-col items-center gap-4 py-16 text-center">
                                    <RiAlertLine
                                        size={28}
                                        className="text-danger"
                                    />
                                    <p className="text-body">
                                        {analysis.failureReason ??
                                            "L'analyse n'a pas pu être générée."}
                                    </p>
                                    <Button
                                        variant="outline"
                                        onClick={requestAnalysis}
                                        disabled={requesting}
                                        className="flex items-center gap-1.5 text-lg"
                                    >
                                        <RiRefreshLine size={14} /> Réessayer
                                    </Button>
                                </div>
                            )}

                            {analysis?.status === 'completed' && (
                                <>
                                    <div className="mb-6 flex items-center justify-between border border-rule bg-surface-2 p-4">
                                        <div>
                                            <div className="font-mono text-lg text-ink-muted uppercase">
                                                Investment Score
                                            </div>
                                            <div className="text-stat-lg">
                                                {analysis.investmentScore}
                                                <span className="text-xl text-ink-muted">
                                                    /100
                                                </span>
                                            </div>
                                        </div>
                                        <button
                                            onClick={requestAnalysis}
                                            disabled={requesting}
                                            className="flex items-center gap-1 font-mono text-lg text-action hover:text-action disabled:opacity-50"
                                        >
                                            <RiRefreshLine size={12} />{' '}
                                            Régénérer
                                        </button>
                                    </div>

                                    {analysis.subScores && (
                                        <div className="mb-6 flex flex-col gap-2">
                                            {Object.entries(
                                                analysis.subScores,
                                            ).map(([key, value]) => (
                                                <div
                                                    key={key}
                                                    className="flex items-center gap-3"
                                                >
                                                    <div className="w-32 flex-shrink-0 text-lg text-ink">
                                                        {subScoreLabels[key] ??
                                                            key}
                                                    </div>
                                                    <div className="h-1.5 flex-1 bg-surface-2">
                                                        <div
                                                            className="bg-surface-20 h-full"
                                                            style={{
                                                                width: `${value}%`,
                                                            }}
                                                        />
                                                    </div>
                                                    <div className="w-8 flex-shrink-0 text-right font-mono text-lg text-ink-muted">
                                                        {value}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}

                                    <Section
                                        title="Résumé exécutif"
                                        body={analysis.executiveSummary}
                                    />
                                    <Section
                                        title="Modèle économique"
                                        body={analysis.businessModelAssessment}
                                    />
                                    <Section
                                        title="Potentiel de marché"
                                        body={analysis.marketPotential}
                                    />
                                    <Section
                                        title="Adéquation problème/solution"
                                        body={analysis.problemSolutionFit}
                                    />
                                    <Section
                                        title="Avantage concurrentiel"
                                        body={analysis.competitiveAdvantage}
                                    />
                                    <Section
                                        title="Équipe"
                                        body={analysis.teamAssessment}
                                    />
                                    <Section
                                        title="Traction"
                                        body={analysis.tractionAssessment}
                                    />
                                    <Section
                                        title="Potentiel financier"
                                        body={analysis.financialPotential}
                                    />
                                    <Section
                                        title="Scalabilité"
                                        body={analysis.scalability}
                                    />
                                    <ListSection
                                        title="Points forts"
                                        items={analysis.strengths}
                                    />
                                    <ListSection
                                        title="Points faibles"
                                        items={analysis.weaknesses}
                                    />
                                    <ListSection
                                        title="Risques"
                                        items={analysis.risks}
                                        tone="warning"
                                    />
                                    <ListSection
                                        title="Signaux d'alerte"
                                        items={analysis.redFlags}
                                        tone="warning"
                                    />
                                    <ListSection
                                        title="Questions de due diligence"
                                        items={analysis.dueDiligenceQuestions}
                                    />
                                    <Section
                                        title="Évaluation du financement demandé"
                                        body={analysis.fundingAssessment}
                                    />
                                    <Section
                                        title="Synthèse"
                                        body={analysis.recommendation}
                                    />
                                </>
                            )}
                        </div>

                        {analysis?.status === 'completed' &&
                            analysis.disclaimer && (
                                <div className="border-t border-rule px-6 py-3 text-lg leading-relaxed text-ink-muted">
                                    {analysis.disclaimer}
                                </div>
                            )}
                    </motion.div>
                </motion.div>
            )}
        </AnimatePresence>
    );
}
