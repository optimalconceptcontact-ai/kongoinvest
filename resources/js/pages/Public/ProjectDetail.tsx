import { Deferred, router, usePage } from '@inertiajs/react';
import {
    RiArrowLeftSLine,
    RiArrowRightSLine,
    RiCloseLine,
    RiDownloadLine,
    RiLinkedinBoxLine,
    RiMailLine,
    RiMapPinLine,
    RiPhoneLine,
    RiPulseLine,
    RiSparklingLine,
} from '@remixicon/react';
import { FormEvent, useEffect, useState } from 'react';
import {
    AIAnalysisSheet,
    type AiAnalysisData,
} from '@/components/AI/AIAnalysisSheet';
import {
    InvestorAdvisorPanel,
    type AiConversationData,
} from '@/components/AI/InvestorAdvisorPanel';
import { SeoHead } from '@/components/SeoHead';
import { Button } from '@/components/ui/button';
import { Link } from '@/components/ui/Link';
import {
    AdvisorPanelSkeleton,
    HeroImageSkeleton,
} from '@/components/ui/page-skeletons';
import { Textarea } from '@/components/ui/Textarea';
import { PublicLayout } from '@/layouts/PublicLayout';
import { provinceLabel } from '@/lib/drcProvinces';
import { useAuthModal } from '@/hooks/useAuthModal';

type ProjectDetailData = {
    id: number;
    slug: string;
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

type ConnectionInfo = {
    id: number;
    status: 'pending' | 'accepted';
    dealRoomId: number | null;
} | null;

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
            className="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 p-4 backdrop-blur-[2px]"
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
                className="absolute top-4 right-4 flex h-11 w-11 items-center justify-center text-paper hover:text-signal focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-signal"
            >
                <RiCloseLine size={28} />
            </button>

            {images.length > 1 && (
                <button
                    type="button"
                    onClick={prev}
                    aria-label="Photo précédente"
                    className="absolute left-2 flex h-11 w-11 items-center justify-center text-paper hover:text-signal focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-signal sm:left-4"
                >
                    <RiArrowLeftSLine size={36} />
                </button>
            )}

            <img
                src={images[index]}
                alt=""
                className="max-h-[85vh] max-w-full object-contain"
            />

            {images.length > 1 && (
                <button
                    type="button"
                    onClick={next}
                    aria-label="Photo suivante"
                    className="absolute right-2 flex h-11 w-11 items-center justify-center text-paper hover:text-signal focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-signal sm:right-4"
                >
                    <RiArrowRightSLine size={36} />
                </button>
            )}

            {images.length > 1 && (
                <div className="absolute bottom-4 font-mono text-lg text-paper">
                    {index + 1} / {images.length}
                </div>
            )}
        </div>
    );
}

type TeamMember = {
    full_name: string;
    role: string;
    linkedin: string;
    email: string;
    phone: string;
};

function Section({ title, body }: { title: string; body: string | null }) {
    if (!body) return null;
    return (
        <div className="border-t border-rule py-6">
            <h2 className="mb-3 text-section-label">{title}</h2>
            <p className="text-body whitespace-pre-line">{body}</p>
        </div>
    );
}

function TeamSection({ members }: { members: TeamMember[] }) {
    if (members.length === 0) return null;

    return (
        <div className="border-t border-rule py-6">
            <h2 className="mb-3 text-section-label">Équipe</h2>
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {members.map((m, i) => (
                    <div
                        key={i}
                        className="border border-rule-strong bg-paper p-4"
                    >
                        <div className="text-item-title">{m.full_name}</div>
                        {m.role && (
                            <div className="mb-2 text-caption">{m.role}</div>
                        )}
                        <div className="flex items-center gap-3 text-ink-muted">
                            {m.linkedin && (
                                <a
                                    href={m.linkedin}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label="LinkedIn"
                                    className="inline-flex h-11 w-11 items-center justify-center border border-transparent text-ink-muted hover:border-rule hover:text-action"
                                >
                                    <RiLinkedinBoxLine size={18} />
                                </a>
                            )}
                            {m.email && (
                                <a
                                    href={`mailto:${m.email}`}
                                    aria-label="Email"
                                    className="inline-flex h-11 w-11 items-center justify-center border border-transparent text-ink-muted hover:border-rule hover:text-action"
                                >
                                    <RiMailLine size={18} />
                                </a>
                            )}
                            {m.phone && (
                                <a
                                    href={`tel:${m.phone}`}
                                    aria-label="Téléphone"
                                    className="inline-flex h-11 w-11 items-center justify-center border border-transparent text-ink-muted hover:border-rule hover:text-action"
                                >
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
    imageUrls?: string[];
    connection: ConnectionInfo;
    aiAnalysis?: AiAnalysisData | null;
    hasAdvisorFeature: boolean;
    advisorConversation?: AiConversationData | null;
    isSaved: boolean;
};

export default function ProjectDetail({
    project,
    imageUrls,
    connection,
    aiAnalysis,
    hasAdvisorFeature,
    advisorConversation,
    isSaved,
}: Props) {
    const [sheetOpen, setSheetOpen] = useState(false);
    const [lightboxIndex, setLightboxIndex] = useState<number | null>(null);
    const { props } = usePage<SharedAuth>();
    const { openRegister } = useAuthModal();
    const isInvestor = props.auth.roles.includes('investor');
    const [showConnectForm, setShowConnectForm] = useState(false);
    const [message, setMessage] = useState('');
    const [sending, setSending] = useState(false);
    const [savePending, setSavePending] = useState(false);

    function toggleSave() {
        setSavePending(true);
        const options = {
            preserveScroll: true,
            onFinish: () => setSavePending(false),
        };
        if (isSaved) {
            router.delete(`/investor/projects/${project.id}/save`, options);
        } else {
            router.post(`/investor/projects/${project.id}/save`, {}, options);
        }
    }
    const target = formatAmount(
        project.fundingTargetMinor,
        project.fundingCurrency,
    );
    const raised = formatAmount(
        project.fundingRaisedMinor,
        project.fundingCurrency,
    );
    const progress =
        project.fundingTargetMinor && project.fundingTargetMinor > 0
            ? Math.min(
                  100,
                  Math.round(
                      ((project.fundingRaisedMinor ?? 0) /
                          project.fundingTargetMinor) *
                          100,
                  ),
              )
            : 0;

    function submitConnectionRequest(e: FormEvent) {
        e.preventDefault();
        setSending(true);
        router.post(
            `/investor/projects/${project.id}/connections`,
            { message },
            {
                onFinish: () => setSending(false),
                onSuccess: () => setShowConnectForm(false),
            },
        );
    }

    function cancelConnection() {
        if (connection)
            router.post(`/investor/connections/${connection.id}/cancel`);
    }

    async function downloadReport() {
        const response = await fetch(`/projects/${project.slug}/report`, {
            credentials: 'same-origin',
        });
        if (!response.ok) {
            return;
        }
        const blob = await response.blob();
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `rapport-projet-${project.slug}.pdf`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    }

    // CTA varies by viewer (spec §110).
    function renderCta() {
        if (project.isOwner) {
            return (
                <Link
                    href={`/entrepreneur/projects/${project.id}/edit`}
                    variant="primary"
                    className="w-full px-4 py-3 text-center"
                >
                    Gérer le projet
                </Link>
            );
        }
        if (!props.auth.user) {
            return (
                <Button
                    type="button"
                    onClick={() => openRegister()}
                    variant="primary"
                    className="w-full px-4 py-3"
                >
                    Créer un compte pour se connecter
                </Button>
            );
        }
        if (isInvestor) {
            if (connection?.status === 'accepted' && connection.dealRoomId) {
                return (
                    <Link
                        href={`/deal-rooms/${connection.dealRoomId}`}
                        variant="secondary"
                        className="block w-full px-4 py-3 text-center"
                    >
                        Ouvrir la Deal Room
                    </Link>
                );
            }
            if (connection?.status === 'pending') {
                return (
                    <div className="flex flex-col gap-2">
                        <div className="border border-rule-strong bg-surface-2 px-3 py-2 text-center text-lg leading-5 text-ink-muted">
                            Demande envoyée — en attente de réponse
                        </div>
                        <Button
                            type="button"
                            onClick={cancelConnection}
                            variant="outline"
                            className="w-full px-4 py-2.5"
                        >
                            Annuler la demande
                        </Button>
                    </div>
                );
            }
            if (showConnectForm) {
                return (
                    <form
                        onSubmit={submitConnectionRequest}
                        className="flex flex-col gap-2"
                    >
                        <Textarea
                            autoFocus
                            rows={3}
                            value={message}
                            onChange={(e) => setMessage(e.target.value)}
                            placeholder="Message d'introduction (optionnel)"
                        />
                        <Button
                            type="submit"
                            disabled={sending}
                            className="w-full px-4 py-3"
                        >
                            {sending ? 'Envoi...' : 'Envoyer la demande'}
                        </Button>
                        <Button
                            type="button"
                            onClick={() => setShowConnectForm(false)}
                            variant="ghost"
                            size="inline"
                            className="font-normal"
                        >
                            Annuler
                        </Button>
                    </form>
                );
            }
            return (
                <div className="flex flex-col gap-2">
                    <Button
                        type="button"
                        onClick={() => setShowConnectForm(true)}
                        className="w-full px-4 py-3"
                    >
                        Se connecter
                    </Button>
                    <button
                        type="button"
                        onClick={toggleSave}
                        disabled={savePending}
                        className={`w-full border px-4 py-3 text-btn disabled:opacity-50 ${
                            isSaved
                                ? 'border-ink bg-ink text-paper'
                                : 'border-rule-strong text-ink hover:border-ink hover:bg-surface-2'
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
                description={
                    project.description.length > 155
                        ? project.description.slice(0, 152) + '...'
                        : project.description
                }
            />
            <div className="mx-auto grid max-w-6xl grid-cols-1 gap-10 px-4 py-12 md:px-6 lg:grid-cols-[1fr_320px]">
                <div>
                    <div className="mb-3 gap-2">
                        {project.sectors.map((s) => (
                            <span
                                key={s.id}
                                className="border border-rule bg-surface-2 px-2 py-1 font-mono text-base text-ink-muted uppercase"
                            >
                                {s.name}
                            </span>
                        ))}    
                    </div>
                    
                    <Button
                            type="button"
                            onClick={downloadReport}
                            variant="secondary"
                            size="sm"
                            className="inline-flex items-center gap-1 border border-rule-strong bg-paper font-mono font-bold tracking-[0.04em] text-ink transition-colors hover:border-ink hover:bg-surface-2 sm:ml-auto"
                        >
                            <RiDownloadLine size={18} />
                            Projet & Analyse IA  (PDF)
                    </Button>
                    <h1 className="mt-6 mb-3 text-page-title-lg">{project.title}</h1>
                    <div className="mb-6 flex flex-wrap items-center gap-4 text-bases">
                        <span className="flex items-center gap-1">
                            <RiMapPinLine size={16} />{' '}
                            {[
                                project.cities.join(' / '),
                                project.provinces
                                    .map(provinceLabel)
                                    .join(' / '),
                            ]
                                .filter(Boolean)
                                .join(', ') || 'RDC'}
                        </span>
                        <span>Par {project.entrepreneurName}</span>
                    </div>
                    <Deferred data="imageUrls" fallback={<HeroImageSkeleton />}>
                        {imageUrls && imageUrls[0] && (
                            <button
                                type="button"
                                onClick={() => setLightboxIndex(0)}
                                className="group relative mb-6 block aspect-[16/9] w-full overflow-hidden border border-rule bg-surface-2"
                            >
                                <img
                                    src={imageUrls[0]}
                                    alt=""
                                    className="h-full w-full object-cover transition group-hover:opacity-90"
                                />
                                {imageUrls.length > 1 && (
                                    <span className="absolute right-2 bottom-2 bg-ink/80 px-2 py-1 font-mono text-lg text-paper">
                                        +{imageUrls.length - 1} photo
                                        {imageUrls.length > 2 ? 's' : ''}
                                    </span>
                                )}
                            </button>
                        )}
                    </Deferred>

                    {lightboxIndex !== null && (
                        <ImageLightbox
                            images={imageUrls ?? []}
                            index={lightboxIndex}
                            onClose={() => setLightboxIndex(null)}
                            onNavigate={setLightboxIndex}
                        />
                    )}

                    
                    <p className="text-body">{project.description}</p>

                    <Section
                        title="Résumé exécutif"
                        body={project.executiveSummary}
                    />
                    <Section title="Problème" body={project.problem} />
                    <Section title="Solution" body={project.solution} />
                    <Section
                        title="Modèle économique"
                        body={project.businessModel}
                    />
                    <Section title="Marché" body={project.market} />
                    <Section
                        title="Avantage concurrentiel"
                        body={project.competitiveAdvantage}
                    />
                    <TeamSection members={project.team} />
                    <Section title="Traction" body={project.traction} />
                    <Section title="Impact" body={project.impact} />
                    <Section title="Risques" body={project.risks} />
                </div>

                <aside className="h-max border border-rule-strong bg-paper p-5 lg:sticky lg:top-24">
                    {target && (
                        <div className="mb-5">
                            <div className="mb-2 flex items-baseline justify-between gap-3">
                                <span className="text-stat">
                                    {raised ?? '0'}
                                </span>
                                <span className="text-right text-caption">
                                    levés sur {target}
                                </span>
                            </div>
                            <div className="h-1.5 w-full bg-surface-2">
                                <div
                                    className="h-full bg-action"
                                    style={{ width: `${progress}%` }}
                                />
                            </div>
                        </div>
                    )}

                    <dl className="mb-5 flex flex-col gap-3 border-y border-rule py-4 text-lg">
                        <div className="flex justify-between gap-4">
                            <dt className="text-ink-muted">Stade</dt>
                            <dd className="text-right font-medium text-ink">
                                {project.stages.map((s) => s.name).join(', ') ||
                                    '—'}
                            </dd>
                        </div>
                        <div className="flex justify-between gap-4">
                            <dt className="text-ink-muted">Secteur</dt>
                            <dd className="text-right font-medium text-ink">
                                {project.sectors
                                    .map((s) => s.name)
                                    .join(', ') || '—'}
                            </dd>
                        </div>
                        <div className="flex items-center justify-between">
                            <dt className="text-ink-muted">Analyse IA</dt>
                            <dd>
                                <Button
                                    type="button"
                                    onClick={() => setSheetOpen(true)}
                                    variant="text"
                                    size="inline"
                                    className="flex items-center gap-1 font-mono font-normal"
                                >
                                    <RiSparklingLine size={14} />{' '}
                                    {aiAnalysis?.status === 'completed'
                                        ? `Score ${aiAnalysis.investmentScore}`
                                        : 'Analyser'}
                                </Button>
                            </dd>
                        </div>
                    </dl>

                    {renderCta()}

                    {project.isOwner === false && hasAdvisorFeature && (
                        <Deferred
                            data="advisorConversation"
                            fallback={<AdvisorPanelSkeleton />}
                        >
                            <InvestorAdvisorPanel
                                projectId={project.id}
                                conversation={advisorConversation ?? null}
                            />
                        </Deferred>
                    )}
                </aside>

                <AIAnalysisSheet
                    open={sheetOpen}
                    onClose={() => setSheetOpen(false)}
                    analysis={aiAnalysis ?? null}
                    projectId={project.id}
                />
            </div>
        </PublicLayout>
    );
}
