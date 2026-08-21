import { usePage } from '@inertiajs/react';
import { SeoHead } from '@/components/SeoHead';
import { RiArrowRightLine, RiPulseLine } from '@remixicon/react';
import { ProjectCard, type ProjectCardData } from '@/components/ProjectCard';
import { Button } from '@/components/Ui/Button';
import { Link } from '@/components/Ui/Link';
import { useAuthModal } from '@/hooks/useAuthModal';
import { PublicLayout } from '@/layouts/PublicLayout';

type Stats = {
    publishedProjects: number;
    entrepreneurs: number;
    investors: number;
    sectors: number;
    investorCountries: number;
};

type Partner = { id: number; name: string; category: string | null; logoPath: string | null; website: string | null };
type Testimonial = { id: number; name: string; role: string | null; company: string | null; content: string };

type Props = {
    stats: Stats;
    featuredProjects: ProjectCardData[];
    partners: Partner[];
    testimonials: Testimonial[];
};

type SharedAuth = { auth: { user: { id: number; name: string } | null } };

function StatBlock({ value, label }: { value: number; label: string }) {
    return (
        <div>
            <div className="text-stat">{value.toLocaleString('fr-FR')}</div>
            <div className="mt-1 text-xs uppercase tracking-wide text-slate-500">{label}</div>
        </div>
    );
}

export default function Welcome({ stats, featuredProjects, partners, testimonials }: Props) {
    const { props } = usePage<SharedAuth>();
    const { openRegister } = useAuthModal();

    return (
        <PublicLayout>
            <SeoHead
                title="Accueil"
                description="Kanda connecte les entrepreneurs de la République Démocratique du Congo avec des investisseurs du monde entier grâce à un moteur de matching IA explicable."
            />

            {/* HERO */}
            <section className="border-b border-slate-200">
                <div className="mx-auto grid max-w-6xl grid-cols-1 items-center gap-14 px-6 py-20 lg:grid-cols-[1.05fr_0.95fr]">
                    <div>
                        <div className="mb-5 flex items-center gap-2 text-eyebrow">
                            <span className="h-px w-4 bg-orange-500" /> Investment-Tech · RDC
                        </div>
                        <h1 className="mb-5 text-5xl font-extrabold leading-tight tracking-tight text-slate-900 sm:text-5xl">
                            Connecter les entrepreneurs <span className="text-blue-600">congolais</span> aux investisseurs du monde entier.
                        </h1>
                        <p className="mb-8 max-w-md text-xl text-slate-500">
                            Une plateforme propulsée par l'IA qui analyse les projets, calcule des scores de
                            compatibilité et met en relation les porteurs de projets basés en RDC avec des
                            investisseurs qualifiés.
                        </p>
                        <div className="mb-12 flex flex-wrap gap-3">
                            {props.auth.user ? (
                                <Link href="/dashboard" variant="cta" className="flex items-center gap-2 px-6 py-3">
                                    Aller à mon tableau de bord <RiArrowRightLine size={16} />
                                </Link>
                            ) : (
                                <Button type="button" onClick={() => openRegister()} variant="cta" className="flex items-center gap-2 px-6 py-3">
                                    Démarrer mon projet <RiArrowRightLine size={16} />
                                </Button>
                            )}
                            <Link href="/projects" variant="outline" className="px-6 py-3">
                                Explorer les opportunités
                            </Link>
                        </div>
                        <div className="grid grid-cols-3 gap-6 border-t border-slate-200 pt-6">
                            <StatBlock value={stats.publishedProjects} label="Projets publiés" />
                            <StatBlock value={stats.investorCountries} label="Pays investisseurs" />
                            <StatBlock value={stats.sectors} label="Secteurs couverts" />
                        </div>
                    </div>

                    <div className="border border-slate-200 bg-slate-50 p-6">
                        <div className="mb-4 flex items-center gap-2 font-mono text-sm text-slate-500">
                            <RiPulseLine size={14} className="text-orange-500" /> MOTEUR DE MATCHING — RÈGLES + IA
                        </div>
                        <p className="mb-4 text-base text-slate-600">
                            Chaque projet publié reçoit un score de compatibilité 0-100 avec chaque investisseur,
                            décomposé critère par critère — jamais une note brute sans explication.
                        </p>
                        <div className="flex flex-col gap-2 border-t border-slate-200 pt-4">
                            {[
                                ['Secteur', 20], ['Financement', 20], ['Stade', 15], ['Modèle économique', 15],
                                ['Géographie', 10], ['Traction', 10], ['Impact', 5], ['Risque', 5],
                            ].map(([label, weight]) => (
                                <div key={label as string} className="flex items-center gap-3">
                                    <div className="w-32 flex-shrink-0 text-sm text-slate-600">{label}</div>
                                    <div className="h-1.5 flex-1 bg-slate-200">
                                        <div className="h-full bg-blue-500" style={{ width: `${weight}%` }} />
                                    </div>
                                    <div className="w-8 flex-shrink-0 text-right font-mono text-[12px] text-slate-500">{weight}%</div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            {/* PARTNERS - hidden entirely when empty, never padded with placeholders */}
            {partners.length > 0 && (
                <section className="border-b border-slate-200 py-8">
                    <div className="mx-auto max-w-6xl px-6">
                        <div className="mb-6 text-eyebrow">Partenaires de confiance</div>
                        <div className="flex flex-wrap items-center gap-4">
                            {partners.map((p) => (
                                <a key={p.id} href={p.website ?? '#'} className="border border-dashed border-slate-300 px-4 py-2 font-mono text-xs text-slate-500 hover:border-slate-900">
                                    {p.name}
                                </a>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {/* HOW IT WORKS (short) */}
            <section className="border-b border-slate-200 py-20">
                <div className="mx-auto max-w-6xl px-6">
                    <div className="mb-12 max-w-lg">
                        <div className="mb-3 text-eyebrow">Comment ça marche</div>
                        <h2 className="text-page-title-lg">Du projet à l'investissement, en toute confiance.</h2>
                    </div>
                    <div className="grid grid-cols-1 border border-slate-200 sm:grid-cols-5">
                        {['Créez votre projet', 'Analyse IA', 'Matching', 'Deal Room', 'Escrow & fonds'].map((step, i) => (
                            <div key={step} className="border-b border-slate-200 p-5 sm:border-b-0 sm:border-r last:border-r-0">
                                <div className="font-mono text-xs font-semibold text-orange-600">{String(i + 1).padStart(2, '0')}</div>
                                <div className="mt-2 text-sm text-item-title">{step}</div>
                            </div>
                        ))}
                    </div>
                    <Link href="/how-it-works" className="mt-6 inline-block underline">
                        En savoir plus →
                    </Link>
                </div>
            </section>

            {/* FEATURED PROJECTS */}
            {featuredProjects.length > 0 && (
                <section className="border-b border-slate-200 bg-slate-50 py-20">
                    <div className="mx-auto max-w-6xl px-6">
                        <div className="mb-12 flex items-end justify-between">
                            <div>
                                <div className="mb-3 text-eyebrow">Projets à la une</div>
                                <h2 className="text-page-title-lg">Des opportunités récemment publiées.</h2>
                            </div>
                            <Link href="/projects" className="hidden underline sm:block">
                                Voir tous les projets →
                            </Link>
                        </div>
                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-3">
                            {featuredProjects.map((project) => (
                                <ProjectCard key={project.slug} project={project} />
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {/* TESTIMONIALS - hidden entirely when empty; never fabricated */}
            {testimonials.length > 0 && (
                <section className="border-b border-slate-200 py-20">
                    <div className="mx-auto max-w-6xl px-6">
                        <div className="mb-12 text-eyebrow">Témoignages</div>
                        <div className="grid grid-cols-1 gap-0 border border-slate-200 sm:grid-cols-3">
                            {testimonials.map((t) => (
                                <div key={t.id} className="border-b border-slate-200 p-6 sm:border-b-0 sm:border-r last:border-r-0">
                                    <p className="mb-4 text-sm text-slate-700">"{t.content}"</p>
                                    <div className="text-sm text-item-title">{t.name}</div>
                                    <div className="text-caption">{[t.role, t.company].filter(Boolean).join(', ')}</div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {/* CTA */}
            <section className="bg-slate-900 py-20 text-center text-white">
                <div className="mx-auto max-w-2xl px-6">
                    <h2 className="mb-3 text-3xl font-extrabold">Prêt à faire avancer votre projet ?</h2>
                    <p className="mb-8 text-slate-300">Rejoignez la plateforme qui connecte la RDC aux capitaux du monde entier.</p>
                    <div className="flex justify-center gap-3">
                        {!props.auth.user && (
                            <Button type="button" onClick={() => openRegister()} variant="cta" className="px-6 py-3">
                                Créer un compte gratuit
                            </Button>
                        )}
                        <Link href="/pricing" variant="outline" className="border-slate-700 px-6 py-3 text-white hover:border-slate-500">
                            Voir les tarifs
                        </Link>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
