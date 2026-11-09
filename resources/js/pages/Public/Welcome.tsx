import { Deferred, usePage } from '@inertiajs/react';
import {
    RiArrowRightLine,
    RiBrainLine,
    RiCheckLine,
    RiCloseLine,
    RiDiscussLine,
    RiFileTextLine,
    RiFilter3Line,
    RiLockLine,
    RiShieldCheckLine,
} from '@remixicon/react';
import { motion } from 'framer-motion';
// import IconCollisionBackground from '@/components/IconCollisionBackground';
import MatchEngineHero from '@/components/MatchEngineHero';
import { PricingGrid } from '@/components/PricingGrid';
import type { Plan } from '@/components/PricingGrid';
import { ProjectCard } from '@/components/ProjectCard';
import type { ProjectCardData } from '@/components/ProjectCard';
import { SeoHead } from '@/components/SeoHead';
import { Button } from '@/components/ui/button';
import { Link } from '@/components/ui/Link';
import {
    FeaturedProjectsSkeleton,
    PricingGridSkeleton,
    StatsRowSkeleton,
} from '@/components/ui/page-skeletons';
import { useAuthModal } from '@/hooks/useAuthModal';
import { PublicLayout } from '@/layouts/PublicLayout';
import { staggerContainer, staggerItem } from '@/lib/animations';

type Stats = {
    publishedProjects: number;
    entrepreneurs: number;
    investors: number;
    sectors: number;
    investorCountries: number;
};

type Partner = {
    id: number;
    name: string;
    category: string | null;
    logoPath: string | null;
    website: string | null;
};
type Testimonial = {
    id: number;
    name: string;
    role: string | null;
    company: string | null;
    content: string;
};

type Props = {
    stats?: Stats;
    featuredProjects?: ProjectCardData[];
    partners: Partner[];
    testimonials: Testimonial[];
    plans?: Plan[];
};

type SharedAuth = {
    auth: { user: { id: number; name: string } | null; roles: string[] };
};

function StatBlock({ value, label }: { value: number; label: string }) {
    return (
        <div>
            <div className="text-4xl font-semibold">{value.toLocaleString('fr-FR')}</div>
            <div className="mt-1 text-eyebrow">{label}</div>
        </div>
    );
}

export default function Welcome({
    stats,
    featuredProjects,
    partners,
    testimonials,
    plans,
}: Props) {
    const { props } = usePage<SharedAuth>();
    const { openLogin, openRegister } = useAuthModal();

    // Featured projects ("À la Une") are investor/admin-only content —
    // guests and entrepreneurs don't see this section at all. The section
    // itself is gated inside <Deferred> so it only renders once the deferred
    // `featuredProjects` prop has resolved.
    const canSeeFeatured =
        props.auth.roles.includes('investor') ||
        props.auth.roles.includes('admin');

    return (
        <PublicLayout>
            <SeoHead
                title="Accueil"
                description="KongoInvest connecte les entrepreneurs de la République Démocratique du Congo avec des investisseurs du monde entier grâce à un moteur de matching IA explicable."
            />

            {/* HERO */}
            <section className="border-b border-rule">
                <div className="mx-auto grid max-w-6xl grid-cols-1 items-start gap-12 px-4 py-16 md:px-6 md:py-24 lg:grid-cols-[1.05fr_0.95fr] lg:gap-16">
                    <motion.div
                        variants={staggerContainer(0.05)}
                        initial="initial"
                        animate="animate"
                    >
                        <motion.div
                            variants={staggerItem}
                            className="mb-5 flex items-center gap-2 text-eyebrow"
                        >
                            <span className="h-1 w-5 bg-signal" />{' '}
                            Investment-Tech · RDC
                        </motion.div>
                        <motion.h1
                            variants={staggerItem}
                            className="mb-6 max-w-[34rem] text-page-title-lg"
                        >
                            Connecter les entrepreneurs congolais aux
                            investisseurs du monde entier.
                        </motion.h1>
                        <motion.p
                            variants={staggerItem}
                            className="mb-8 max-w-[32rem] text-body"
                        >
                            Une plateforme propulsée par l'IA qui analyse les
                            projets, calcule des scores de compatibilité et met
                            en relation les porteurs de projets basés en RDC
                            avec des investisseurs qualifiés.
                        </motion.p>
                        <motion.div
                            variants={staggerItem}
                            className="mb-12 flex flex-wrap gap-3"
                        >
                            {props.auth.user ? (
                                <Link
                                    href="/dashboard"
                                    variant="primary"
                                    className="flex items-center gap-2 px-6 py-3"
                                >
                                    Aller à mon tableau de bord{' '}
                                    <RiArrowRightLine size={16} />
                                </Link>
                            ) : (
                                <Button
                                    type="button"
                                    onClick={() => openRegister()}
                                    variant="primary"
                                    className="flex items-center gap-2 px-6 py-3"
                                >
                                    Démarrer mon projet{' '}
                                    <RiArrowRightLine size={16} />
                                </Button>
                            )}
                            {props.auth.user ? (
                                !props.auth.roles.includes('entrepreneur') && (
                                    <Link
                                        href="/projects"
                                        variant="outline"
                                        className="px-6 py-3"
                                    >
                                        Explorer les opportunités
                                    </Link>
                                )
                            ) : (
                                <Button
                                    type="button"
                                    onClick={() => openLogin()}
                                    variant="outline"
                                    className="px-6 py-3"
                                >
                                    Explorer les opportunités
                                </Button>
                            )}
                        </motion.div>
                        <motion.div
                            variants={staggerItem}
                            className="border-t border-rule pt-6"
                        >
                            <Deferred
                                data="stats"
                                fallback={<StatsRowSkeleton />}
                            >
                                <div className="grid grid-cols-3 gap-6">
                                    <StatBlock
                                        value={stats?.publishedProjects ?? 0}
                                        label="Projets publiés"
                                    />
                                    <StatBlock
                                        value={stats?.investorCountries ?? 0}
                                        label="Pays investisseurs"
                                    />
                                    <StatBlock
                                        value={stats?.sectors ?? 0}
                                        label="Secteurs couverts"
                                    />
                                </div>
                            </Deferred>
                        </motion.div>
                    </motion.div>

                    <div className="h-[380px] overflow-hidden border border-rule-strong bg-surface-2 sm:h-[480px] lg:h-[560px]">
                        <MatchEngineHero />
                    </div>
                </div>
            </section>

            {/* PARTNERS - hidden entirely when empty, never padded with placeholders */}
            {partners.length > 0 && (
                <section className="border-b border-rule py-10">
                    <div className="mx-auto max-w-6xl px-4 md:px-6">
                        <div className="mb-3 flex items-center gap-2 text-eyebrow">
                            <span className="h-1 w-5 bg-signal" />
                            Partenaires de confiance
                        </div>
                        <div className="flex flex-wrap items-center gap-3">
                            
                            {partners.map((p) => (
                                <a
                                    key={p.id}
                                    href={p.website ?? '#'}
                                    className="border border-rule px-4 py-2 font-mono text-lg text-ink-muted transition-colors hover:border-ink hover:text-ink"
                                >
                                    {p.name}
                                </a>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {/* PROBLEM & SOLUTION */}
            <section className="border-b border-rule py-16 md:py-24">
                <div className="mx-auto max-w-6xl px-4 md:px-6">
                    <div className="mb-12 max-w-2xl">
                        <div className="mb-3 flex items-center gap-2 text-eyebrow">
                            <span className="h-1 w-5 bg-signal" /> Diagnostic
                            & Solution
                        </div>
                        <h2 className="text-section-title">
                            Le fossé du capital en RDC, et notre manière de le
                            combler.
                        </h2>
                        <p className="mt-3 text-body">
                            Les projets à fort potentiel existent partout en
                            République Démocratique du Congo, et les
                            investisseurs mondiaux recherchent de la croissance
                            réelle. Pourtant, les deux mondes peinaient
                            jusqu'ici à se rencontrer.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 border border-rule-strong md:grid-cols-2">
                        {/* Le Problème */}
                        <div className="border-b border-rule bg-paper p-8 md:border-r md:border-b-0">
                            <div className="mb-6 flex items-center gap-3">
                                <span className="flex h-8 w-8 items-center justify-center border border-rule bg-danger/20 text-ink">
                                    <RiCloseLine
                                        size={24}
                                        className="text-ink-muted"
                                    />
                                </span>
                                <div>
                                    <div className="text-section-label">
                                        Le constat
                                    </div>
                                    <h3 className="font-mono text-xl font-bold text-ink">
                                        Les freins structurels actuels
                                    </h3>
                                </div>
                            </div>
                            <div className="space-y-6">
                                <div>
                                    <h4 className="flex items-center gap-2 font-mono text-xl font-semibold text-ink">
                                        <span className="h-2 w-2 bg-ink-muted" />
                                        Réseaux fermés et opaques
                                    </h4>
                                    <p className="mt-1.5 text-lg leading-6 text-ink-muted">
                                        Les financements circulent presque
                                        uniquement par relations personnelles et
                                        cercles restreints. Les porteurs de
                                        projets sans réseau établi restent
                                        invisibles.
                                    </p>
                                </div>
                                <div className="border-t border-rule pt-6">
                                    <h4 className="flex items-center gap-2 font-mono text-xl font-semibold text-ink">
                                        <span className="s bg-ink-muted" />
                                        Asymétrie d'information
                                    </h4>
                                    <p className="mt-1.5 text-lg leading-6 text-ink-muted">
                                        Aucun espace centralisé ne permettait
                                        aux investisseurs d'évaluer, de comparer
                                        et de suivre des opportunités concrètes
                                        et auditées en RDC.
                                    </p>
                                </div>
                                <div className="border-t border-rule pt-6">
                                    <h4 className="flex items-center gap-2 font-mono text-xl font-semibold text-ink">
                                        <span className="h-2 w-2 bg-ink-muted" />
                                        Méfiance et insécurité des capitaux
                                    </h4>
                                    <p className="mt-1.5 text-lg leading-6 text-ink-muted">
                                        Sans cadre de négociation formalisé ni
                                        mécanisme de séquestre des fonds, le
                                        risque perçu freine les intentions
                                        d'investissement.
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* La Solution */}
                        <div className="bg-paper p-8">
                            <div className="mb-6 flex items-center gap-3">
                                <span className="flex h-8 w-8 items-center justify-center border border-rule bg-success/20 text-ink">
                                    <RiCheckLine
                                        size={24}
                                        className="font-bold text-ink"
                                    />
                                </span>
                                <div>
                                    <div className="text-section-label text-ink">
                                        Notre réponse
                                    </div>
                                    <h3 className="font-mono text-xl font-bold text-ink">
                                        L'infrastructure KongoInvest
                                    </h3>
                                </div>
                            </div>
                            <div className="space-y-6">
                                <div>
                                    <h4 className="flex items-center gap-2 font-mono text-xl font-semibold text-ink">
                                        <span className="h-2 w-2 bg-signal" />
                                        Mise en relation directe et équitable
                                    </h4>
                                    <p className="mt-1.5 text-lg leading-6 text-ink-muted">
                                        Chaque entrepreneur congolais est jugé
                                        sur la solidité réelle de son projet, de
                                        ses chiffres et de son impact, sans
                                        barrière d'intermédiaires.
                                    </p>
                                </div>
                                <div className="border-t border-rule pt-6">
                                    <h4 className="flex items-center gap-2 font-mono text-xl font-semibold text-ink">
                                        <span className="h-2 w-2 bg-signal" />
                                        Matching explicable par IA
                                    </h4>
                                    <p className="mt-1.5 text-lg leading-6 text-ink-muted">
                                        Un score objectif 0-100 décomposé
                                        critère par critère pour chaque thèse
                                        d'investissement : aucune boîte noire,
                                        une clarté totale dès le premier
                                        contact.
                                    </p>
                                </div>
                                <div className="border-t border-rule pt-6">
                                    <h4 className="flex items-center gap-2 font-mono text-xl font-semibold text-ink">
                                        <span className="h-2 w-2 bg-signal" />
                                        Deal Rooms & Séquestre (Escrow)
                                    </h4>
                                    <p className="mt-1.5 text-lg leading-6 text-ink-muted">
                                        Vérification d'identité (KYC), salons de
                                        négociation confidentiels et fonds
                                        retenus en séquestre, libérés après
                                        confirmation de l'investisseur — ou
                                        remboursés.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* HOW IT WORKS (short) */}
            <section className="relative border-b border-rule py-16 md:py-24">
                {/* <IconCollisionBackground
                    count={134}
                    padding={18}
                    opacity={0.1}
                /> */}
                <div className="relative mx-auto max-w-6xl px-4 md:px-6">
                    <div className="mb-12 max-w-xl">
                        <div className="mb-3 flex items-center gap-2 text-eyebrow">
                            <span className="h-1 w-5 bg-signal" /> Comment ça marche?
                        </div>
                        
                        <h2 className="text-section-title">
                            Du projet à l'investissement, en toute confiance.
                        </h2>
                    </div>
                    <div className="grid grid-cols-1 border border-rule-strong sm:grid-cols-3 lg:grid-cols-5">
                        {[
                            [
                                'Créez votre projet',
                                'Décrivez votre activité basée en RDC : modèle économique, marché, équipe, traction et objectif de financement.',
                            ],
                            [
                                'Analyse IA',
                                "L'IA évalue chaque projet publié et produit une analyse structurée de ses forces et de ses points de vigilance.",
                            ],
                            [
                                'Matching',
                                'Un score de compatibilité 0-100 est calculé avec chaque investisseur, décomposé critère par critère.',
                            ],
                            [
                                'Deal Room',
                                "En cas d'intérêt mutuel, les deux parties négocient dans un espace dédié : messages, documents et contrat.",
                            ],
                            [
                                'Escrow & fonds',
                                'Les fonds sont retenus en séquestre puis libérés après confirmation de l’investisseur — ou remboursés en cas de litige.',
                            ],
                        ].map(([step, description], i) => (
                            <div
                                key={step}
                                className="border-b border-rule bg-paper p-5 sm:border-r sm:border-b-0 sm:last:border-r-0"
                            >
                                <div className="flex items-center gap-2 font-mono text-lg font-bold text-ink-muted">
                                    <span className="h-[3px] w-3 bg-signal" />
                                    {String(i + 1).padStart(2, '0')}
                                </div>
                                <div className="mt-3 text-item-title">
                                    {step}
                                </div>
                                <p className="mt-2 text-lg leading-6 text-ink-muted">
                                    {description}
                                </p>
                            </div>
                        ))}
                    </div>
                    <Link
                        href="/how-it-works"
                        className="mt-8 inline-block underline"
                    >
                        En savoir plus →
                    </Link>
                </div>
            </section>

            {/* CORE FEATURES */}
            <section className="border-b border-rule py-16 md:py-24">
                <div className="mx-auto max-w-6xl px-4 md:px-6">
                    <div className="mb-12 max-w-xl">
                        
                        <div className="mb-3 flex items-center gap-2 text-eyebrow">
                            <span className="h-1 w-5 bg-signal" />{' '}
                            Fonctionnalités clés
                        </div>
                        <h2 className="text-section-title">
                            Une suite d'outils pensée pour sécuriser chaque
                            transaction.
                        </h2>
                        <p className="mt-3 text-body">
                            De la détection d'opportunités au versement des
                            capitaux, tout est intégré pour éliminer les
                            frictions et instaurer une confiance durable.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 gap-px border border-rule-strong bg-rule sm:grid-cols-2 lg:grid-cols-3">
                        {/* Feature 1 */}
                        <div className="bg-paper p-6">
                            <div className="mb-5 flex items-center justify-between">
                                <span className="flex h-10 w-10 items-center justify-center border border-rule bg-surface-2 text-ink">
                                    <RiBrainLine size={20} />
                                </span>
                                <span className="text-eyebrow">Algorithme</span>
                            </div>
                            <div className="text-item-title">
                                Matching IA explicable
                            </div>
                            <p className="mt-2 text-body">
                                Un score de compatibilité calculé selon vos
                                thèses sectorielles et financières, avec une
                                décomposition critère par critère pour chaque
                                opportunité.
                            </p>
                        </div>

                        {/* Feature 2 */}
                        <div className="bg-paper p-6">
                            <div className="mb-5 flex items-center justify-between">
                                <span className="flex h-10 w-10 items-center justify-center border border-rule bg-surface-2 text-ink">
                                    <RiDiscussLine size={20} />
                                </span>
                                <span className="text-eyebrow">
                                    Collaboration
                                </span>
                            </div>
                            <div className="text-item-title">
                                Deal Rooms privées
                            </div>
                            <p className="mt-2 text-body">
                                Un espace sécurisé pour chaque mise en relation
                                : messagerie, partage de pièces justificatives
                                sensibles et négociation directe des clauses.
                            </p>
                        </div>

                        {/* Feature 3 */}
                        <div className="bg-paper p-6">
                            <div className="mb-5 flex items-center justify-between">
                                <span className="flex h-10 w-10 items-center justify-center border border-rule bg-surface-2 text-ink">
                                    <RiShieldCheckLine size={20} />
                                </span>
                                <span className="text-eyebrow">Garantie</span>
                            </div>
                            <div className="text-item-title">
                                Paiements sous séquestre
                            </div>
                            <p className="mt-2 text-body">
                                Les fonds sont retenus en séquestre et ne sont
                                libérés à l'entrepreneur qu'après confirmation
                                de l'investisseur — ou remboursés en cas de
                                litige.
                            </p>
                        </div>

                        {/* Feature 4 */}
                        <div className="bg-paper p-6">
                            <div className="mb-5 flex items-center justify-between">
                                <span className="flex h-10 w-10 items-center justify-center border border-rule bg-surface-2 text-ink">
                                    <RiLockLine size={20} />
                                </span>
                                <span className="text-eyebrow">Conformité</span>
                            </div>
                            <div className="text-item-title">
                                Vérification d'identité (KYC)
                            </div>
                            <p className="mt-2 text-body">
                                Vérification de l'identité des fondateurs et des
                                investisseurs avant toute opération engageante,
                                avec des pièces conservées en privé et servies
                                via des liens signés à durée limitée.
                            </p>
                        </div>

                        {/* Feature 5 */}
                        <div className="bg-paper p-6">
                            <div className="mb-5 flex items-center justify-between">
                                <span className="flex h-10 w-10 items-center justify-center border border-rule bg-surface-2 text-ink">
                                    <RiFileTextLine size={20} />
                                </span>
                                <span className="text-eyebrow">Juridique</span>
                            </div>
                            <div className="text-item-title">
                                Contrats & Droit OHADA
                            </div>
                            <p className="mt-2 text-body">
                                Génération automatique d'accords
                                d'investissement et de pactes d'associés
                                pré-remplis à partir des négociations,
                                juridiquement alignés avec le droit en RDC.
                            </p>
                        </div>

                        {/* Feature 6 */}
                        <div className="bg-paper p-6">
                            <div className="mb-5 flex items-center justify-between">
                                <span className="flex h-10 w-10 items-center justify-center border border-rule bg-surface-2 text-ink">
                                    <RiFilter3Line size={20} />
                                </span>
                                <span className="text-eyebrow">Ciblage</span>
                            </div>
                            <div className="text-item-title">
                                Thèses d'investissement sur-mesure
                            </div>
                            <p className="mt-2 text-body">
                                Configurez vos critères prioritaires (secteur,
                                stade, ticket min/max, zone géographique) et
                                recevez une notification dès qu'un nouveau match
                                est calculé pour votre thèse.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {/* FEATURED PROJECTS - investors and admins only */}
            {canSeeFeatured && (
                <Deferred
                    data="featuredProjects"
                    fallback={<FeaturedProjectsSkeleton />}
                >
                    {featuredProjects && featuredProjects.length > 0 ? (
                        <section className="border-b border-rule py-16 md:py-24">
                            <div className="mx-auto max-w-6xl px-4 md:px-6">
                                <div className="mb-12 flex items-end justify-between">
                                    <div>
                                        
                                        <div className="mb-3 flex items-center gap-2 text-eyebrow">
                                            <span className="h-1 w-5 bg-signal" />
                                            Projets à la une
                                        </div>
                                        <h2 className="text-section-title">
                                            Des opportunités récemment publiées.
                                        </h2>
                                    </div>
                                    {props.auth.user ? (
                                        !props.auth.roles.includes('entrepreneur') && (
                                            <Link
                                                href="/projects"
                                                className="hidden underline sm:block"
                                            >
                                                Voir tous les projets →
                                            </Link>
                                        )
                                    ) : (
                                        <button
                                            type="button"
                                            onClick={() => openLogin()}
                                            className="hidden cursor-pointer font-mono text-lg text-action underline underline-offset-4 hover:text-ink sm:block"
                                        >
                                            Se connecter pour voir tous les projets →
                                        </button>
                                    )}
                                </div>
                                <div className="grid grid-cols-1 gap-6 sm:grid-cols-4">
                                    {featuredProjects.map((project) => (
                                        <ProjectCard
                                            key={project.slug}
                                            project={project}
                                        />
                                    ))}
                                </div>
                            </div>
                        </section>
                    ) : null}
                </Deferred>
            )}

            {/* PRICING - same role-aware grid as the Pricing page */}
            <section className="border-b border-rule py-16 md:py-24">
                <div className="mx-auto max-w-6xl px-4 md:px-6">
                    <div className="mb-12 flex items-end justify-between">
                        <div>
                            <div className="mb-3 flex items-center gap-2 text-eyebrow">
                                <span className="h-1 w-5 bg-signal" />
                                Tarifs
                            </div>
                            <h2 className="text-section-title">
                                Un plan pour chaque étape.
                            </h2>
                        </div>
                        <Link
                            href="/pricing"
                            className="hidden underline sm:block"
                        >
                            Voir tous les tarifs →
                        </Link>
                    </div>
                    <Deferred data="plans" fallback={<PricingGridSkeleton />}>
                        <PricingGrid plans={plans ?? []} />
                    </Deferred>
                </div>
            </section>

            {/* FAQ */}
            <section className="border-b border-rule py-16 md:py-24">
                <div className="mx-auto max-w-6xl px-4 md:px-6">
                    <div className="mb-12 flex items-end justify-between">
                        <div>
                            
                            <div className="mb-3 flex items-center gap-2 text-eyebrow">
                                <span className="h-1 w-5 bg-signal" />
                                Questions fréquentes
                            </div>
                            <h2 className="text-section-title">
                                Réponses à vos questions.
                            </h2>
                        </div>
                        <Link href="/faq" className="hidden underline sm:block">
                            Consulter toute la FAQ →
                        </Link>
                    </div>

                    <div className="border-t border-rule-strong">
                        {[
                            {
                                question:
                                    'Comment fonctionne le moteur de matching par IA ?',
                                answer: "Le moteur de matching combine deux approches : des règles objectives et déterministes (secteurs d'activité, stade d'avancement, ticket financier, appétence au risque) et une analyse qualitative assistée par IA sur le modèle économique, la traction et l'impact. Le résultat est un score transparent sur 100, décomposé critère par critère.",
                            },
                            {
                                question:
                                    'KongoInvest est-elle réservée aux projets basés en RDC ?',
                                answer: "Oui pour les entrepreneurs : tous les projets publiés doivent être implantés ou opérer en République Démocratique du Congo. Cette règle est appliquée au niveau de la base de données, pas seulement dans l'interface. En revanche, les investisseurs viennent du monde entier (diaspora congolaise, fonds panafricains, investisseurs d'impact internationaux).",
                            },
                            {
                                question:
                                    'Comment les fonds et les transactions sont-ils sécurisés ?',
                                answer: "Les transactions passent par un mécanisme de séquestre (Escrow). Les fonds sont retenus jusqu'à ce que l'investisseur confirme la libération au profit de l'entrepreneur. En cas de désaccord, l'investisseur peut demander un remboursement ou ouvrir un litige, qui suspend la libération.",
                            },
                            {
                                question:
                                    'Combien coûte l’utilisation de la plateforme ?',
                                answer: "L'inscription et le plan Free sont gratuits : un projet actif et un quota mensuel de jetons IA. Les abonnements Entrepreneur, Investor et Coaching ajoutent des projets actifs, des quotas IA plus élevés et, selon le plan, l'accès aux Deal Rooms, au KYC, aux contrats et au séquestre. Le détail figure dans la grille tarifaire.",
                            },
                            {
                                question:
                                    'Que se passe-t-il après un match entre un entrepreneur et un investisseur ?',
                                answer: "Dès qu'un intérêt réciproque est manifesté, une Deal Room confidentielle peut être ouverte. Les deux parties y échangent des messages, partagent des documents, formalisent leur contrat d'investissement et déclenchent la procédure de séquestre.",
                            },
                            {
                                question:
                                    'Faut-il avoir déjà une société immatriculée pour publier un projet ?',
                                answer: "Vous pouvez publier un projet dès le stade d'idée structurée ou de prototype afin de mesurer l'intérêt des investisseurs. En revanche, la vérification d'identité (KYC) est requise avant de signer un contrat d'investissement et de recevoir des fonds par séquestre.",
                            },
                        ].map((item, idx) => (
                            <details
                                key={idx}
                                className="group border-b border-rule"
                            >
                                <summary className="flex min-h-12 cursor-pointer list-none items-center justify-between py-5 text-item-title select-none">
                                    <span className="pr-4">
                                        {item.question}
                                    </span>
                                    <span className="font-mono text-2xl font-semibold text-ink transition-transform duration-200 group-open:rotate-45">
                                        +
                                    </span>
                                </summary>
                                <p className="max-w-3xl pb-5 text-lg leading-8 text-ink-muted">
                                    {item.answer}
                                </p>
                            </details>
                        ))}
                    </div>

                    <div className="mt-8 sm:hidden">
                        <Link href="/faq" className="underline">
                            Consulter toute la FAQ →
                        </Link>
                    </div>
                </div>
            </section>

            {/* TESTIMONIALS - hidden entirely when empty; never fabricated */}
            {testimonials.length > 0 && (
                <section className="border-b border-rule py-16 md:py-24">
                    <div className="mx-auto max-w-6xl px-4 md:px-6">
                        
                        
                        <div className="mb-3 flex items-center gap-2 text-eyebrow">
                                <span className="h-1 w-5 bg-signal" />
                                Témoignages
                            </div>
                        <div className="grid grid-cols-1 gap-0 border border-rule-strong sm:grid-cols-4">
                            {testimonials.map((t) => (
                                <div
                                    key={t.id}
                                    className="border-b border-rule bg-paper p-6 sm:border-r sm:border-b-0 sm:last:border-r-0"
                                >
                                    <p className="mb-5 text-lg leading-6 text-ink">
                                        "{t.content}"
                                    </p>
                                    <div className="text-xl font-semibold text-ink">
                                        {t.name}
                                    </div>
                                    <div className="text-caption">
                                        {[t.role, t.company]
                                            .filter(Boolean)
                                            .join(', ')}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {/* CTA */}
            <section className="border-t-4 border-t-signal bg-ink py-16 text-center text-paper md:py-24">
                <div className="mx-auto max-w-3xl px-4 md:px-6">
                    <h2 className="mb-4 font-mono text-4xl font-bold tracking-[-0.03em] md:text-6xl">
                        Prêt à faire avancer votre projet ?
                    </h2>
                    <p className="mb-8 text-body">
                        Rejoignez la plateforme qui connecte la RDC aux capitaux
                        du monde entier.
                    </p>
                    <div className="flex flex-wrap justify-center gap-3">
                        {!props.auth.user && (
                            <Button
                                type="button"
                                onClick={() => openRegister()}
                                variant="secondary"
                                className="px-6 py-3"
                            >
                                Créer un compte gratuit
                            </Button>
                        )}
                        <Link
                            href="/pricing"
                            variant="outline"
                            className="border-paper/40 px-6 py-3 text-paper hover:border-paper hover:bg-paper/10"
                        >
                            Voir les tarifs
                        </Link>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
