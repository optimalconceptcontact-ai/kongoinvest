import { useState } from 'react';
import { SeoHead } from '@/components/SeoHead';
import { PublicLayout } from '@/layouts/PublicLayout';

// ─── Types ───────────────────────────────────────────────────────────────────

type InstrumentItem = {
    id: number;
    name: string;
    slug: string;
    code: string;
    description: string | null;
    instrument_category: string;
    repayment_required: boolean;
    ownership_dilution: boolean;
    conversion_possible: boolean;
    collateral_possible: boolean;
    remuneration_type: string | null;
};

type FinancingTypeItem = {
    id: number;
    name: string;
    slug: string;
    code: string;
    description: string | null;
    icon: string | null;
    color: string | null;
    instruments: InstrumentItem[];
};

type StageItem = {
    id: number;
    key: string;
    name: string;
    description: string | null;
    order: number;
};

type FinancingSourceItem = {
    id: number;
    name: string;
    slug: string;
    code: string;
    description: string | null;
    source_category: string | null;
};

type FinancingChannelItem = {
    id: number;
    name: string;
    slug: string;
    code: string;
    description: string | null;
    requires_intermediary: boolean;
};

type GuideArticleItem = {
    id: number;
    section: string;
    title: string;
    description: string | null;
    content: string;
};

type Props = {
    financingTypes: FinancingTypeItem[];
    instruments: InstrumentItem[];
    stages: StageItem[];
    financingSources: FinancingSourceItem[];
    financingChannels: FinancingChannelItem[];
    guideArticles: GuideArticleItem[];
    activeTab: string;
};

// ─── Static content ───────────────────────────────────────────────────────────

const OHADA_CONTENT = [
    {
        key: 'ohada-intro',
        title: "Qu'est-ce que le droit OHADA ?",
        body: "L'OHADA (Organisation pour l'Harmonisation en Afrique du Droit des Affaires) est un traité international signé en 1993 qui unifie le droit des affaires dans 17 États membres d'Afrique, dont la République démocratique du Congo. Les Actes Uniformes OHADA s'appliquent directement et priment sur le droit national en matière commerciale et sociétaire.",
    },
    {
        key: 'ohada-formes',
        title: 'Formes juridiques applicables en RDC',
        body: "En droit OHADA, les principales formes sociétaires sont : la SARL (Société à Responsabilité Limitée), la SA (Société Anonyme), la SNC (Société en Nom Collectif) et la SCS (Société en Commandite Simple). La SAS (Société par Actions Simplifiée) n'est pas encore reconnue par l'Acte Uniforme OHADA. Les entreprises individuelles peuvent opter pour l'EURL.",
    },
    {
        key: 'safe-restriction',
        title: 'SAFE et instruments non incorporés en RDC',
        body: "Le SAFE (Simple Agreement for Future Equity) est un instrument de financement d'origine américaine, non encore reconnu par le droit OHADA. Son utilisation en RDC requiert une analyse juridique approfondie. KongoInvest le répertorie à titre informatif, mais recommande de recourir à un avocat spécialisé avant toute utilisation dans une opération locale.",
    },
    {
        key: 'rccm',
        title: 'Enregistrement RCCM et NIF',
        body: "Le RCCM (Registre du Commerce et du Crédit Mobilier) est le registre officiel des entreprises en RDC. Toute société doit y être immatriculée pour exercer légalement. Le NIF (Numéro d'Identification Fiscale) est attribué par la Direction Générale des Impôts. Ces deux identifiants sont requis pour toute opération d'investissement formelle.",
    },
    {
        key: 'bcc-regulation',
        title: 'Réglementation de la BCC',
        body: "La Banque Centrale du Congo (BCC) supervise les établissements de crédit, les institutions de microfinance et les prestataires de services financiers. Certaines opérations d'investissement, notamment les prêts et levées de fonds publics, peuvent être soumises à des autorisations spécifiques de la BCC.",
    },
    {
        key: 'ubo',
        title: 'Bénéficiaires effectifs (UBO) et conformité AML',
        body: "La RDC applique les recommandations du GAFI (Groupe d'Action Financière) en matière de lutte contre le blanchiment et le financement du terrorisme. Toute opération d'investissement significative doit identifier les bénéficiaires effectifs (UBO) jusqu'au seuil de détention applicable. KongoInvest intègre ce processus dans son module KYC/KYB.",
    },
];

const GLOSSARY_CONTENT = [
    {
        key: 'term-sheet',
        title: 'Term Sheet',
        body: "Document non contraignant résumant les conditions principales d'un accord d'investissement : valorisation, montant investi, type de titres, droits préférentiels, clauses de liquidation. Il sert de base à la rédaction des documents juridiques définitifs.",
    },
    {
        key: 'cap-table',
        title: 'Cap Table (Tableau de capitalisation)',
        body: "Tableau récapitulatif de la structure de capital d'une entreprise, indiquant l'identité des actionnaires, le nombre et le type de titres détenus, et le pourcentage de détention de chacun. Évolue à chaque tour de financement.",
    },
    {
        key: 'pre-money',
        title: 'Valorisation pré-money / post-money',
        body: "La valorisation pré-money est la valeur de l'entreprise avant la levée de fonds. La valorisation post-money est la valeur après investissement (pré-money + montant investi). Le pourcentage de dilution de l'entrepreneur dépend du rapport entre le montant investi et la valorisation post-money.",
    },
    {
        key: 'dilution',
        title: 'Dilution',
        body: "Réduction du pourcentage de participation d'un actionnaire lors d'une augmentation de capital. Exemple : un fondateur détenant 100 % avant une levée de fonds se retrouvera avec 80 % si l'investisseur entre à hauteur de 20 % du capital post-money.",
    },
    {
        key: 'ticket',
        title: 'Ticket d\'investissement',
        body: "Montant minimum ou typique investi par un investisseur dans une opération. Les business angels interviennent généralement entre 10 000 $ et 150 000 $, les fonds de capital-risque entre 500 000 $ et plusieurs millions.",
    },
    {
        key: 'due-diligence',
        title: 'Due Diligence (Audit d\'acquisition)',
        body: "Processus d'examen approfondi d'une entreprise avant un investissement, couvrant les aspects financiers, juridiques, fiscaux, opérationnels et commerciaux. Permet à l'investisseur de valider les hypothèses du business plan et d'identifier les risques.",
    },
    {
        key: 'convertible',
        title: 'Obligation convertible / Note convertible',
        body: "Instrument de dette qui peut être converti en capital (actions) lors d'un événement déclencheur (prochain tour de financement, date, etc.). Permet de financer une startup avant que sa valorisation ne soit fixée.",
    },
    {
        key: 'mezzanine',
        title: 'Financement mezzanine',
        body: "Forme de financement hybride combinant dette et capital. Généralement subordonné à la dette senior, le mezzanine offre un rendement plus élevé et peut inclure des warrants (bons de souscription d'actions).",
    },
    {
        key: 'exit',
        title: 'Sortie (Exit)',
        body: "Mécanisme par lequel un investisseur réalise son investissement : introduction en bourse (IPO), cession à un industriel (M&A), rachat par les fondateurs (buyback), ou cession secondaire à un autre fonds.",
    },
    {
        key: 'blended-finance',
        title: 'Financement mixte (Blended Finance)',
        body: "Structure combinant plusieurs instruments de financement (par exemple, capital + dette + subvention) pour optimiser le profil risque/rendement d'une opération. Fréquent dans les projets d'infrastructure ou d'impact en Afrique.",
    },
];

// ─── Helpers ─────────────────────────────────────────────────────────────────

const TABS = [
    { key: 'financing-types', label: 'Types de financement' },
    { key: 'financial-instruments', label: 'Instruments financiers' },
    { key: 'stages', label: "Stades de l'entreprise" },
    { key: 'financing-sources', label: 'Sources de financement' },
    { key: 'financing-channels', label: 'Canaux de financement' },
    { key: 'ohada-compliance', label: 'Conformité OHADA / RDC' },
    { key: 'glossary', label: 'Glossaire' },
] as const;

type TabKey = (typeof TABS)[number]['key'];

const CATEGORY_LABELS: Record<string, string> = {
    equity: 'Fonds propres',
    debt: 'Dette',
    hybrid: 'Hybride',
    grant: 'Subvention',
    revenue_based: 'Partage de revenus',
    alternative: 'Alternatif',
};

const SOURCE_CATEGORY_LABELS: Record<string, string> = {
    private: 'Secteur privé',
    institutional: 'Institutionnel',
    public: 'Public',
    community: 'Communautaire',
};

function Badge({
    label,
    variant = 'default',
}: {
    label: string;
    variant?: 'default' | 'warning' | 'success' | 'muted';
}) {
    const cls = {
        default: 'bg-surface-2 text-ink',
        warning: 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        success: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
        muted: 'bg-surface-2 text-ink-muted',
    }[variant];

    return (
        <span className={`inline-block border border-rule px-1.5 py-0.5 font-mono text-xs ${cls}`}>
            {label}
        </span>
    );
}

function Accordion({
    title,
    children,
    defaultOpen = false,
}: {
    title: string;
    children: React.ReactNode;
    defaultOpen?: boolean;
}) {
    return (
        <details open={defaultOpen} className="border-b border-rule">
            <summary className="flex min-h-12 cursor-pointer list-none items-center justify-between py-5 text-item-title select-none">
                <span className="pr-4">{title}</span>
                <span className="font-mono text-2xl font-semibold text-ink transition-transform duration-200">
                    +
                </span>
            </summary>
            <div className="pb-5">{children}</div>
        </details>
    );
}

// ─── Section components ───────────────────────────────────────────────────────

const DEFAULT_OHADA_ARTICLES: GuideArticleItem[] = OHADA_CONTENT.map(
    (item, index) => ({
        id: -(index + 1),
        section: 'ohada-compliance',
        title: item.title,
        description: null,
        content: `<p>${item.body}</p>`,
    }),
);

const DEFAULT_GLOSSARY_ARTICLES: GuideArticleItem[] = GLOSSARY_CONTENT.map(
    (item, index) => ({
        id: -(index + 1),
        section: 'glossary',
        title: item.title,
        description: null,
        content: `<p>${item.body}</p>`,
    }),
);

/**
 * Editorial articles written by an admin, rendered as full articles —
 * title, lead paragraph, then rich text — instead of FAQ accordions.
 */
function GuideArticles({ articles }: { articles: GuideArticleItem[] }) {
    if (articles.length === 0) {
        return null;
    }

    return (
        <div className="mb-10 flex flex-col gap-12">
            {articles.map((article) => (
                <article
                    key={article.id}
                    className="border-t border-rule pt-8 first:border-t-0 first:pt-0"
                >
                    <h2 className="text-section-title">{article.title}</h2>
                    {article.description && (
                        <p className="mt-3 max-w-2xl text-xl leading-7 text-ink-muted">
                            {article.description}
                        </p>
                    )}
                    <div
                        className="article-body mt-6 max-w-2xl"
                        dangerouslySetInnerHTML={{
                            __html: article.content,
                        }}
                    />
                </article>
            ))}
        </div>
    );
}

function FinancingTypesSection({ types }: { types: FinancingTypeItem[] }) {
    return (
        <div className="mb-10">
            <p className="mb-6 max-w-2xl text-body text-ink-muted">
                KongoInvest classe les financements en six grandes familles. Chaque type regroupe des instruments adaptés à différents profils de risque, de rendement et de gouvernance.
            </p>
            {types.map((t) => (
                <Accordion key={t.id} title={t.name}>
                    {t.description && (
                        <p className="mb-4 max-w-xl text-body text-ink-muted">{t.description}</p>
                    )}
                    {t.instruments.length > 0 && (
                        <div>
                            <p className="mb-2 font-mono text-xs tracking-widest text-ink-muted uppercase">
                                Instruments disponibles
                            </p>
                            <div className="flex flex-wrap gap-2">
                                {t.instruments.map((i) => (
                                    <span
                                        key={i.id}
                                        className="border border-rule px-2 py-1 font-mono text-sm text-ink"
                                    >
                                        {i.name}
                                    </span>
                                ))}
                            </div>
                        </div>
                    )}
                </Accordion>
            ))}
        </div>
    );
}

function FinancialInstrumentsSection({ instruments }: { instruments: InstrumentItem[] }) {
    const grouped = instruments.reduce<Record<string, InstrumentItem[]>>((acc, i) => {
        const cat = i.instrument_category ?? 'other';
        (acc[cat] ??= []).push(i);
        return acc;
    }, {});

    return (
        <div className="mb-10">
            <p className="mb-6 max-w-2xl text-body text-ink-muted">
                Les instruments financiers sont les outils concrets utilisés pour structurer un investissement. Chaque instrument possède des caractéristiques propres en matière de dilution, de remboursement et de rémunération.
            </p>
            {Object.entries(grouped).map(([cat, items]) => (
                <div key={cat} className="mb-8">
                    <h3 className="mb-3 text-section-label">
                        {CATEGORY_LABELS[cat] ?? cat}
                    </h3>
                    {items.map((i) => (
                        <Accordion key={i.id} title={i.name}>
                            <div className="space-y-3">
                                {i.description && (
                                    <p className="max-w-xl text-body text-ink-muted">
                                        {i.description}
                                    </p>
                                )}
                                <div className="flex flex-wrap gap-2">
                                    {i.ownership_dilution && (
                                        <Badge label="Dilution" variant="warning" />
                                    )}
                                    {i.repayment_required && (
                                        <Badge label="Remboursable" variant="default" />
                                    )}
                                    {i.conversion_possible && (
                                        <Badge label="Convertible" variant="success" />
                                    )}
                                    {i.collateral_possible && (
                                        <Badge label="Garantie possible" variant="muted" />
                                    )}
                                    {i.remuneration_type && (
                                        <Badge label={i.remuneration_type} variant="muted" />
                                    )}
                                </div>
                            </div>
                        </Accordion>
                    ))}
                </div>
            ))}
        </div>
    );
}

const STAGE_DETAILS: Record<string, { description: string; characteristics: string[] }> = {
    idea: {
        description: "Le projet n'existe qu'à l'état de concept. Aucun produit, aucune validation marché. L'équipe est souvent réduite à un ou deux fondateurs.",
        characteristics: [
            "Pas de revenus",
            "Risque maximal",
            "Financement typique : love money, amorçage, subvention innovation",
            "Ticket moyen : < 50 000 $",
        ],
    },
    pre_seed: {
        description: "Premier prototype ou MVP en cours de développement. Les fondateurs testent leurs hypothèses auprès des premiers utilisateurs potentiels.",
        characteristics: [
            "Produit en développement",
            "Premières validations qualitatives",
            "Financement typique : business angels, fonds d'amorçage",
            "Ticket moyen : 50 000 $ – 250 000 $",
        ],
    },
    seed: {
        description: "Le MVP est disponible. Les premières ventes ou utilisateurs sont acquis. L'équipe s'étoffe et les premiers indicateurs de traction émergent.",
        characteristics: [
            "Traction initiale démontrée",
            "Product-market fit en cours de validation",
            "Financement typique : fonds Seed, business angels syndiqués",
            "Ticket moyen : 250 000 $ – 1 M$",
        ],
    },
    early_stage: {
        description: "Le product-market fit est atteint. L'entreprise génère des revenus récurrents et cherche à accélérer sa croissance.",
        characteristics: [
            "Revenus en croissance",
            "Modèle économique validé",
            "Financement typique : Série A, capital-risque",
            "Ticket moyen : 1 M$ – 5 M$",
        ],
    },
    growth: {
        description: "L'entreprise est en phase d'expansion : nouveaux marchés, recrutement intensif, développement produit. Elle peut envisager des financements bancaires complémentaires.",
        characteristics: [
            "Forte croissance des revenus",
            "Profitabilité possible",
            "Financement typique : Série B/C, dette senior, mezzanine",
            "Ticket moyen : 5 M$ – 50 M$",
        ],
    },
    mature: {
        description: "Entreprise établie avec une position de marché solide. Les opérations sont profitables. Les financements visent l'optimisation de la structure du capital.",
        characteristics: [
            "Rentabilité consolidée",
            "Génération de cash-flow stable",
            "Financement typique : private equity, obligations, financement bancaire",
            "Ticket moyen : > 20 M$",
        ],
    },
    pre_ipo: {
        description: "L'entreprise se prépare à une introduction en bourse ou à une cession stratégique. Elle consolide sa gouvernance, ses états financiers et sa conformité réglementaire.",
        characteristics: [
            "Gouvernance renforcée",
            "Audit et transparence financière",
            "Financement typique : pre-IPO round, capital-investissement de sortie",
            "Ticket moyen : > 50 M$",
        ],
    },
};

function StagesSection({ stages }: { stages: StageItem[] }) {
    return (
        <div className="mb-10">
            <p className="mb-6 max-w-2xl text-body text-ink-muted">
                Le stade de développement d'une entreprise détermine les types de financement accessibles, le niveau de risque perçu par les investisseurs et les exigences en matière de gouvernance et de conformité.
            </p>
            {stages.map((s) => {
                const detail = STAGE_DETAILS[s.key];
                return (
                    <Accordion key={s.id} title={s.name}>
                        <div className="space-y-3">
                            <p className="max-w-xl text-body text-ink-muted">
                                {detail?.description ?? s.description ?? ''}
                            </p>
                            {detail?.characteristics && (
                                <ul className="space-y-1">
                                    {detail.characteristics.map((c) => (
                                        <li
                                            key={c}
                                            className="flex items-start gap-2 text-body text-ink-muted"
                                        >
                                            <span className="mt-1.5 h-1.5 w-1.5 flex-shrink-0 bg-ink" />
                                            {c}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    </Accordion>
                );
            })}
        </div>
    );
}

function FinancingSourcesSection({ sources }: { sources: FinancingSourceItem[] }) {
    const grouped = sources.reduce<Record<string, FinancingSourceItem[]>>((acc, s) => {
        const cat = s.source_category ?? 'other';
        (acc[cat] ??= []).push(s);
        return acc;
    }, {});

    return (
        <div className="mb-10">
            <p className="mb-6 max-w-2xl text-body text-ink-muted">
                Les sources de financement désignent les catégories d'acteurs qui fournissent les ressources financières. Chaque source a ses propres critères de sélection, horizons d'investissement et attentes de rendement.
            </p>
            {Object.entries(grouped).map(([cat, items]) => (
                <div key={cat} className="mb-8">
                    <h3 className="mb-3 text-section-label">
                        {SOURCE_CATEGORY_LABELS[cat] ?? cat}
                    </h3>
                    {items.map((s) => (
                        <Accordion key={s.id} title={s.name}>
                            <p className="max-w-xl text-body text-ink-muted">
                                {s.description ?? 'Aucune description disponible.'}
                            </p>
                        </Accordion>
                    ))}
                </div>
            ))}
        </div>
    );
}

function FinancingChannelsSection({ channels }: { channels: FinancingChannelItem[] }) {
    return (
        <div className="mb-10">
            <p className="mb-6 max-w-2xl text-body text-ink-muted">
                Les canaux de financement représentent les voies par lesquelles les ressources financières sont mobilisées et acheminées vers les projets. Ils conditionnent le cadre réglementaire applicable et les intermédiaires impliqués.
            </p>
            {channels.map((c) => (
                <Accordion key={c.id} title={c.name}>
                    <div className="space-y-3">
                        {c.description && (
                            <p className="max-w-xl text-body text-ink-muted">{c.description}</p>
                        )}
                        {c.requires_intermediary && (
                            <Badge label="Intermédiaire requis" variant="warning" />
                        )}
                    </div>
                </Accordion>
            ))}
        </div>
    );
}

function OhadaSection({ articles }: { articles: GuideArticleItem[] }) {
    return (
        <div className="mb-10">
            <p className="mb-6 max-w-2xl text-body text-ink-muted">
                Les opérations d'investissement en RDC sont encadrées par le droit OHADA et la réglementation nationale. Ces éléments sont essentiels pour structurer correctement tout financement.
            </p>
            <GuideArticles
                articles={articles.length > 0 ? articles : DEFAULT_OHADA_ARTICLES}
            />
        </div>
    );
}

function GlossarySection({ articles }: { articles: GuideArticleItem[] }) {
    return (
        <div className="mb-10">
            <p className="mb-6 max-w-2xl text-body text-ink-muted">
                Lexique des termes clés utilisés dans le domaine du financement d'entreprise et de l'investissement.
            </p>
            <GuideArticles
                articles={articles.length > 0 ? articles : DEFAULT_GLOSSARY_ARTICLES}
            />
        </div>
    );
}

// ─── Main page ────────────────────────────────────────────────────────────────

export default function Guide({
    financingTypes,
    instruments,
    stages,
    financingSources,
    financingChannels,
    guideArticles,
    activeTab,
}: Props) {
    const [activeCategory, setActiveCategory] = useState<TabKey>(
        (activeTab as TabKey) ?? 'financing-types',
    );

    function articlesFor(section: TabKey) {
        return (guideArticles ?? []).filter(
            (article) => article.section === section,
        );
    }

    return (
        <PublicLayout>
            <SeoHead
                title="Guide — Financement & Investissement"
                description="Guide complet sur les types de financement, instruments financiers, stades d'entreprise, sources et canaux de financement en RDC. Conformité OHADA et glossaire."
            />
            <div className="mx-auto max-w-3xl px-4 py-16 md:px-6">
                <p className="text-eyebrow">Guide</p>
                <h1 className="mt-2 mb-4 text-page-title-lg">
                    Financement & Investissement.
                </h1>
                <p className="mb-8 max-w-2xl text-xl leading-7 text-ink-muted">
                    Tout ce qu'il faut savoir pour structurer un financement ou préparer une levée de fonds en RDC&nbsp;: types et instruments financiers, stades de développement, sources et canaux de financement, cadre juridique OHADA et glossaire.
                </p>

                {/* Category tabs */}
                <div className="mb-10 flex flex-wrap gap-2">
                    {TABS.map((tab) => (
                        <button
                            key={tab.key}
                            onClick={() => setActiveCategory(tab.key)}
                            className={`min-h-11 border px-3 py-1.5 text-btn ${
                                activeCategory === tab.key
                                    ? 'border-ink bg-ink text-paper'
                                    : 'border-rule-strong text-ink hover:border-ink'
                            }`}
                        >
                            {tab.label}
                        </button>
                    ))}
                </div>

                {/* Section content */}
                {activeCategory === 'financing-types' && (
                    <>
                        <GuideArticles articles={articlesFor('financing-types')} />
                        <FinancingTypesSection types={financingTypes} />
                    </>
                )}
                {activeCategory === 'financial-instruments' && (
                    <>
                        <GuideArticles
                            articles={articlesFor('financial-instruments')}
                        />
                        <FinancialInstrumentsSection
                            instruments={instruments}
                        />
                    </>
                )}
                {activeCategory === 'stages' && (
                    <>
                        <GuideArticles articles={articlesFor('stages')} />
                        <StagesSection stages={stages} />
                    </>
                )}
                {activeCategory === 'financing-sources' && (
                    <>
                        <GuideArticles
                            articles={articlesFor('financing-sources')}
                        />
                        <FinancingSourcesSection sources={financingSources} />
                    </>
                )}
                {activeCategory === 'financing-channels' && (
                    <>
                        <GuideArticles
                            articles={articlesFor('financing-channels')}
                        />
                        <FinancingChannelsSection
                            channels={financingChannels}
                        />
                    </>
                )}
                {activeCategory === 'ohada-compliance' && (
                    <OhadaSection articles={articlesFor('ohada-compliance')} />
                )}
                {activeCategory === 'glossary' && (
                    <GlossarySection articles={articlesFor('glossary')} />
                )}
            </div>
        </PublicLayout>
    );
}
