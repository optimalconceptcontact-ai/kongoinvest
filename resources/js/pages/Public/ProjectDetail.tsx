import { router, usePage } from '@inertiajs/react';
import { RiArrowLeftSLine, RiArrowRightSLine, RiCloseLine, RiLinkedinBoxLine, RiMailLine, RiMapPinLine, RiPhoneLine, RiPulseLine, RiSparklingLine } from '@remixicon/react';
import { FormEvent, useEffect, useState } from 'react';
import { AIAnalysisSheet, type AiAnalysisData } from '@/components/AI/AIAnalysisSheet';
import { InvestorAdvisorPanel, type AiConversationData } from '@/components/AI/InvestorAdvisorPanel';
import { SeoHead } from '@/components/SeoHead';
import { Button } from '@/components/Ui/Button';
import { Link } from '@/components/Ui/Link';
import { Textarea } from '@/components/Ui/Textarea';
import { PublicLayout } from '@/layouts/PublicLayout';
import { provinceLabel } from '@/lib/drcProvinces';

type ProjectDetailData = {
    id: number;
    title: string;
    description: string;
    provinces: string[];
    cities: string[];
    fundingTargetMinor: number | null;
    fundingRaisedMinor: number | null;
    fundingCurrency: string;
    entrepreneurName: string;
    isOwner: boolean;
    sectors: { id: number; name: string }[];
    stages: { id: number; name: string }[];
    executiveSummary: string | null;
    problem: string | null;
    solution: string | null;
    businessModel: string | null;
    market: string | null;
    competitiveAdvantage: string | null;
    team: TeamMember[];
    traction: string | null;
    impact: string | null;
    risks: string | null;
};

type SharedAuth = { auth: { user: { id: number } | null; roles: string[] } };

type ConnectionInfo = { id: number; status: 'pending' | 'accepted'; dealRoomId: number | null } | null;

function ImageLightbox({
    images,
    index,
    onClose,
    onNavigate,
}: {
    images: string[];
    index: number;
    onClose: () => void;
    onNavigate: (i: number) => void;
}) {
    const prev = () => onNavigate((index - 1 + images.length) % images.length);
    const next = () => onNavigate((index + 1) % images.length);

    useEffect(() => {
        function onKeyDown(e: KeyboardEvent) {
            if (e.key === 'Escape') onClose();
            if (e.key === 'ArrowLeft') prev();
            if (e.key === 'ArrowRight') next();
        }

        document.addEventListener('keydown', onKeyDown);
        return () => document.removeEventListener('keydown', onKeyDown);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [index]);

    return (
        <div
            className="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/90 p-4"
            role="dialog"
            aria-modal="true"
            onMouseDown={(e) => {
                if (e.target === e.currentTarget) onClose();
            }}
        >
            <button
                type="button"
                onClick={onClose}
                aria-label="Fermer"
                className="absolute top-4 right-4 text-white hover:text-slate-400"
            >
                <RiCloseLine size={28} />
            </button>

            {images.length > 1 && (
                <button
                    type="button"
                    onClick={prev}
                    aria-label="Photo précédente"
                    className="absolute left-2 text-white hover:text-slate-400 sm:left-4"
                >
                    <RiArrowLeftSLine size={36} />
                </button>
            )}

            <img src={images[index]} alt="" className="max-h-[85vh] max-w-full object-contain" />

            {images.length > 1 && (
                <button
                    type="button"
                    onClick={next}
                    aria-label="Photo suivante"
                    className="absolute right-2 text-white hover:text-slate-400 sm:right-4"
                >
                    <RiArrowRightSLine size={36} />
                </button>
            )}

            {images.length > 1 && (
                <div className="absolute bottom-4 font-mono text-xs text-white">
                    {index + 1} / {images.length}
                </div>
            )}
        </div>
    );
}

type TeamMember = { full_name: string; role: string; linkedin: string; email: string; phone: string };

function Section({ title, body }: { title: string; body: string | null }) {
    if (!body) return null;
    return (
        <div className="border-t border-slate-300 py-6">
            <h2 className="mb-3 text-section-label">{title}</h2>
            <p className="whitespace-pre-line text-lg leading-relaxed text-slate-700">{body}</p>
        </div>
    );
}

function TeamSection({ members }: { members: TeamMember[] }) {
    if (members.length === 0) return null;

    return (
        <div className="border-t border-slate-300 py-6">
            <h2 className="mb-3 text-section-label">Équipe</h2>
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {members.map((m, i) => (
                    <div key={i} className="border border-slate-300 p-4">
                        <div className="text-item-title">{m.full_name}</div>
                        {m.role && <div className="mb-2 text-caption">{m.role}</div>}
                        <div className="flex items-center gap-3 text-slate-400">
                            {m.linkedin && (
                                <a href={m.linkedin} target="_blank" rel="noopener noreferrer" aria-label="LinkedIn" className="hover:text-blue-600">
                                    <RiLinkedinBoxLine size={18} />
                                </a>
                            )}
                            {m.email && (
                                <a href={`mailto:${m.email}`} aria-label="Email" className="hover:text-blue-600">
                                    <RiMailLine size={18} />
                                </a>
                            )}
                            {m.phone && (
                                <a href={`tel:${m.phone}`} aria-label="Téléphone" className="hover:text-blue-600">
                                    <RiPhoneLine size={18} />
                                </a>
                            )}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

function formatAmount(minor: number | null, currency: string) {
    if (minor === null) return null;
    return `${(minor / 100).toLocaleString('fr-FR')} ${currency}`;
}

type Props = {
    project: ProjectDetailData;
    imageUrls: string[];
    connection: ConnectionInfo;
    aiAnalysis: AiAnalysisData | null;
    hasAdvisorFeature: boolean;
    advisorConversation: AiConversationData | null;
    isSaved: boolean;
};

export default function ProjectDetail({ project, imageUrls, connection, aiAnalysis, hasAdvisorFeature, advisorConversation, isSaved }: Props) {
    const [sheetOpen, setSheetOpen] = useState(false);
    const [lightboxIndex, setLightboxIndex] = useState<number | null>(null);
    const { props } = usePage<SharedAuth>();
    const isInvestor = props.auth.roles.includes('investor');
    const [showConnectForm, setShowConnectForm] = useState(false);
    const [message, setMessage] = useState('');
    const [sending, setSending] = useState(false);
    const [savePending, setSavePending] = useState(false);

    function toggleSave() {
        setSavePending(true);
        const options = { preserveScroll: true, onFinish: () => setSavePending(false) };
        if (isSaved) {
            router.delete(`/investor/projects/${project.id}/save`, options);
        } else {
            router.post(`/investor/projects/${project.id}/save`, {}, options);
        }
    }
    const target = formatAmount(project.fundingTargetMinor, project.fundingCurrency);
    const raised = formatAmount(project.fundingRaisedMinor, project.fundingCurrency);
    const progress =
        project.fundingTargetMinor && project.fundingTargetMinor > 0
            ? Math.min(100, Math.round(((project.fundingRaisedMinor ?? 0) / project.fundingTargetMinor) * 100))
            : 0;

    function submitConnectionRequest(e: FormEvent) {
        e.preventDefault();
        setSending(true);
        router.post(
            `/investor/projects/${project.id}/connections`,
            { message },
            { onFinish: () => setSending(false), onSuccess: () => setShowConnectForm(false) },
        );
    }

    function cancelConnection() {
        if (connection) router.post(`/investor/connections/${connection.id}/cancel`);
    }

    // CTA varies by viewer (spec §110).
    function renderCta() {
        if (project.isOwner) {
            return (
                <Link href={`/entrepreneur/projects/${project.id}/edit`} variant="primary" className="btn-primary w-full px-4 py-3 text-center">
                    Gérer le projet
                </Link>
            );
        }
        if (!props.auth.user) {
            return (
                <Button type="button" variant="cta" className="w-full px-4 py-3">
                    Créer un compte pour se connecter
                </Button>
            );
        }
        if (isInvestor) {
            if (connection?.status === 'accepted' && connection.dealRoomId) {
                return (
                    <Link href={`/deal-rooms/${connection.dealRoomId}`} variant="secondary" className="block w-full px-4 py-3 text-center">
                        Ouvrir la Deal Room
                    </Link>
                );
            }
            if (connection?.status === 'pending') {
                return (
                    <div className="flex flex-col gap-2">
                        <div className="border border-orange-200 bg-orange-50 px-3 py-2 text-center text-xs text-orange-700">
                            Demande envoyée — en attente de réponse
                        </div>
                        <Button
                            type="button"
                            onClick={cancelConnection}
                            variant="outline"
                            className="w-full px-4 py-2.5 text-xs text-slate-500 hover:border-orange-600 hover:text-orange-600"
                        >
                            Annuler la demande
                        </Button>
                    </div>
                );
            }
            if (showConnectForm) {
                return (
                    <form onSubmit={submitConnectionRequest} className="flex flex-col gap-2">
                        <Textarea
                            autoFocus
                            rows={3}
                            value={message}
                            onChange={(e) => setMessage(e.target.value)}
                            placeholder="Message d'introduction (optionnel)"
                        />
                        <Button type="submit" disabled={sending} className="w-full px-4 py-3">
                            {sending ? 'Envoi...' : 'Envoyer la demande'}
                        </Button>
                        <Button type="button" onClick={() => setShowConnectForm(false)} variant="ghost" size="inline" className="font-normal">
                            Annuler
                        </Button>
                    </form>
                );
            }
            return (
                <div className="flex flex-col gap-2">
                    <Button type="button" onClick={() => setShowConnectForm(true)} className="w-full px-4 py-3">
                        Se connecter
                    </Button>
                    <button
                        type="button"
                        onClick={toggleSave}
                        disabled={savePending}
                        className={`w-full border px-4 py-3 text-btn disabled:opacity-50 ${
                            isSaved ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-400 text-slate-900 hover:border-slate-900'
                        }`}
                    >
                        {isSaved ? 'Sauvegardé ✓' : 'Sauvegarder'}
                    </button>
                </div>
            );
        }
        return null;
    }

    return (
        <PublicLayout>
            <SeoHead
                title={project.title}
                description={project.description.length > 155 ? project.description.slice(0, 152) + '...' : project.description}
            />
            <div className="mx-auto grid max-w-6xl grid-cols-1 gap-10 px-6 py-12 lg:grid-cols-[1fr_320px]">
                <div>
                    {imageUrls[0] && (
                        <button
                            type="button"
                            onClick={() => setLightboxIndex(0)}
                            className="group relative mb-6 block aspect-[16/9] w-full overflow-hidden bg-slate-100"
                        >
                            <img
                                src={imageUrls[0]}
                                alt=""
                                className="h-full w-full object-cover transition group-hover:opacity-90"
                            />
                            {imageUrls.length > 1 && (
                                <span className="absolute right-2 bottom-2 bg-slate-900/70 px-2 py-1 font-mono text-[11px] text-white">
                                    +{imageUrls.length - 1} photo{imageUrls.length > 2 ? 's' : ''}
                                </span>
                            )}
                        </button>
                    )}

                    {lightboxIndex !== null && (
                        <ImageLightbox
                            images={imageUrls}
                            index={lightboxIndex}
                            onClose={() => setLightboxIndex(null)}
                            onNavigate={setLightboxIndex}
                        />
                    )}

                    <div className="mb-2 flex gap-2">
                        {project.sectors.map((s) => (
                            <span key={s.id} className="border border-orange-200 bg-orange-50 px-2 py-1 font-mono text-[11px] uppercase text-orange-700">
                                {s.name}
                            </span>
                        ))}
                    </div>
                    <h1 className="mb-3 text-page-title-lg">{project.title}</h1>
                    <div className="mb-6 flex items-center gap-4 text-body">
                        <span className="flex items-center gap-1"><RiMapPinLine size={16} /> {[project.cities.join(' / '), project.provinces.map(provinceLabel).join(' / ')].filter(Boolean).join(', ') || 'RDC'}</span>
                        <span>Par {project.entrepreneurName}</span>
                    </div>
                    <p className="text-base leading-relaxed text-slate-700">{project.description}</p>

                    <Section title="Résumé exécutif" body={project.executiveSummary} />
                    <Section title="Problème" body={project.problem} />
                    <Section title="Solution" body={project.solution} />
                    <Section title="Modèle économique" body={project.businessModel} />
                    <Section title="Marché" body={project.market} />
                    <Section title="Avantage concurrentiel" body={project.competitiveAdvantage} />
                    <TeamSection members={project.team} />
                    <Section title="Traction" body={project.traction} />
                    <Section title="Impact" body={project.impact} />
                    <Section title="Risques" body={project.risks} />
                </div>

                <aside className="h-max border border-slate-300 p-5">
                    {target && (
                        <div className="mb-5">
                            <div className="mb-1 flex items-baseline justify-between">
                                <span className="font-mono text-lg font-bold text-slate-900">{raised ?? '0'}</span>
                                <span className="text-caption">levés sur {target}</span>
                            </div>
                            <div className="h-1.5 w-full bg-slate-100">
                                <div className="h-full bg-blue-500" style={{ width: `${progress}%` }} />
                            </div>
                        </div>
                    )}

                    <dl className="mb-5 flex flex-col gap-3 border-y border-slate-100 py-4 text-sm">
                        <div className="flex justify-between"><dt className="text-slate-500">Stade</dt><dd className="font-medium text-slate-900">{project.stages.map((s) => s.name).join(', ') || '—'}</dd></div>
                        <div className="flex justify-between"><dt className="text-slate-500">Secteur</dt><dd className="font-medium text-slate-900">{project.sectors.map((s) => s.name).join(', ') || '—'}</dd></div>
                        <div className="flex justify-between items-center">
                            <dt className="text-slate-500">Analyse IA</dt>
                            <dd>
                                <Button
                                    type="button"
                                    onClick={() => setSheetOpen(true)}
                                    variant="text"
                                    size="inline"
                                    className="flex items-center gap-1 font-mono font-normal"
                                >
                                    <RiSparklingLine size={14} /> {aiAnalysis?.status === 'completed' ? `Score ${aiAnalysis.investmentScore}` : 'Analyser'}
                                </Button>
                            </dd>
                        </div>
                    </dl>

                    {renderCta()}

                    {project.isOwner === false && hasAdvisorFeature && (
                        <InvestorAdvisorPanel projectId={project.id} conversation={advisorConversation} />
                    )}
                </aside>

                <AIAnalysisSheet open={sheetOpen} onClose={() => setSheetOpen(false)} analysis={aiAnalysis} projectId={project.id} />
            </div>
        </PublicLayout>
    );
}
