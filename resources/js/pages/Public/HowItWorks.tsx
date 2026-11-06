import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { SeoHead } from '@/components/SeoHead';
import { Button } from '@/components/ui/button';
import { Link } from '@/components/ui/Link';
import { useAuthModal } from '@/hooks/useAuthModal';
import { PublicLayout } from '@/layouts/PublicLayout';

const paths = {
    entrepreneur: [
        [
            'Créez votre compte',
            "Inscription gratuite en tant qu'entrepreneur — quelques minutes suffisent pour renseigner votre identité et vos coordonnées. Votre projet sera automatiquement rattaché à la RDC : c'est une règle fondamentale de la plateforme, appliquée jusque dans la base de données, et elle vaut pour tous les entrepreneurs sans exception.",
        ],
        [
            'Décrivez votre projet',
            "Vous remplissez le portrait complet de votre projet : modèle économique, marché visé, équipe, traction déjà obtenue, besoin de financement, secteurs et stades. Plus votre description est précise et honnête, plus le moteur de matching peut évaluer finement la compatibilité avec les investisseurs — c'est la matière première de tout le reste.",
        ],
        [
            'Publiez',
            "Une fois les champs essentiels renseignés — au minimum un secteur, un stade et un objectif de financement — le bouton « Publier » se débloque et votre projet devient visible aux investisseurs. La vérification est faite côté serveur, pas seulement dans l'interface : impossible de publier un projet incomplet en contournant le formulaire.",
        ],
        [
            'Recevez vos matches',
            "Le moteur de matching calcule un score de compatibilité entre votre projet et chaque investisseur actif, en combinant règles objectives (secteur, stade, financement, géographie, risque) et analyse qualitative du modèle économique, de la traction et de l'impact. Le détail du calcul est affiché critère par critère : vous voyez exactement pourquoi chaque investisseur vous est proposé.",
        ],
        [
            "Passez à l'étape suivante",
            "Quand un match débouche sur un intérêt mutuel, la plateforme vous accompagne vers la concrétisation : connexion directe, Deal Room structurée pour négocier, vérification KYC, contrat généré à partir des conditions convenues et séquestre (Escrow) pour sécuriser les fonds jusqu'à confirmation des deux parties.",
        ],
    ],
    investor: [
        [
            'Créez votre compte',
            "Inscription en tant qu'investisseur — disponible depuis n'importe quel pays. Contrairement aux entrepreneurs, qui doivent être basés en RDC, les investisseurs viennent du monde entier : Europe, Amérique du Nord, Asie, Afrique ou ailleurs, tous apportent des perspectives et des capitaux différents.",
        ],
        [
            'Définissez votre thèse',
            "C'est le cœur de votre profil : secteurs et stades multiples, ticket minimum et maximum, appétence au risque, géographies préférées. Cette thèse n'est pas figée — vous pouvez l'affiner à tout moment depuis votre profil investisseur, et vos recommandations sont recalculées immédiatement après chaque modification.",
        ],
        [
            'Découvrez des projets',
            "Parcourez les projets publiés par les entrepreneurs congolais, filtrés selon vos critères : secteur d'activité, stade de maturité ou mot-clé. Chaque fiche projet présente le modèle économique, le marché, l'équipe, la traction et l'objectif de financement — assez d'information pour une première évaluation sérieuse.",
        ],
        [
            'Consultez vos matches',
            "Chaque projet compatible avec votre thèse reçoit un score de compatibilité sur 100, décomposé critère par critère : vous voyez la part des règles objectives (secteur, stade, ticket, géographie, risque) et celle de l'analyse qualitative (modèle économique, traction, impact). Aucune note brute sans explication — vous savez toujours pourquoi un projet vous est proposé.",
        ],
        [
            "Passez à l'étape suivante",
            "Un score élevé n'est que le début de la conversation. Vous prenez contact avec l'entrepreneur, la discussion continue dans une Deal Room structurée, puis vient la vérification KYC des deux côtés, le contrat et enfin le séquestre (Escrow) qui protège votre investissement jusqu'à confirmation des livrables.",
        ],
    ],
} as const;

const prerequisites = {
    entrepreneur: {
        title: 'Ce dont vous aurez besoin avant de commencer',
        items: [
            'Une idée claire ou un projet en activité, basé en République Démocratique du Congo.',
            'De quoi décrire votre modèle économique et votre marché en quelques phrases solides.',
            'Votre stade de maturité (idée, prototype, premières ventes, croissance…) et votre objectif de financement chiffré.',
            'Toute traction existante : clients, revenus, partenariats, prototypes — même modeste, elle pèse dans le score de matching.',
        ],
    },
    investor: {
        title: 'Ce dont vous aurez besoin avant de commencer',
        items: [
            "Une thèse d'investissement même approximative : elle servira de point de départ au matching.",
            "Une fourchette de ticket (minimum et maximum) réaliste pour votre capacité d'investissement.",
            "Votre niveau d'appétence au risque et vos géographies d'intérêt.",
            'Quelques minutes pour explorer les projets publiés — la découverte est libre, aucun engagement avant un match.',
        ],
    },
} as const;

type SharedAuth = {
    auth: { user: { id: number; name: string } | null; roles: string[] };
};

export default function HowItWorks() {
    const { props } = usePage<SharedAuth>();
    const isEntrepreneur = props.auth?.roles?.includes('entrepreneur');
    const [tab, setTab] = useState<'entrepreneur' | 'investor'>('entrepreneur');
    const { openRegister, openLogin } = useAuthModal();
    const prereq = prerequisites[tab];

    return (
        <PublicLayout>
            <SeoHead
                title="Comment ça marche"
                description="Le parcours entrepreneur et investisseur sur KongoInvest, étape par étape, de l'inscription à l'investissement."
            />
            <div className="mx-auto max-w-4xl px-4 py-16 md:px-6">
                <p className="text-eyebrow">Comment ça marche</p>
                <h1 className="mt-2 mb-4 text-page-title-lg">
                    Un parcours structuré, des deux côtés.
                </h1>
                <p className="mb-10 max-w-2xl text-2xl leading-relaxed text-ink-muted">
                    Que vous soyez porteur de projet en RDC ou investisseur
                    partout ailleurs dans le monde, la plateforme vous fait
                    avancer par étapes claires : chacune a un objectif précis,
                    et aucune ne demande d'engagement financier avant que la
                    rencontre ne soit confirmée des deux côtés.
                </p>

                <div className="mb-10 flex w-max border border-rule-strong">
                    {(['entrepreneur', 'investor'] as const).map((key) => (
                        <button
                            key={key}
                            onClick={() => setTab(key)}
                            className={`min-h-11 px-5 py-2.5 text-btn ${
                                tab === key
                                    ? 'bg-ink text-paper'
                                    : 'bg-paper text-ink-muted hover:text-ink'
                            } ${key === 'entrepreneur' ? 'border-r border-rule-strong' : ''}`}
                        >
                            {key === 'entrepreneur'
                                ? 'Entrepreneur'
                                : 'Investisseur'}
                        </button>
                    ))}
                </div>

                <div className="border border-rule-strong bg-paper">
                    {paths[tab].map(([title, body], i) => (
                        <div
                            key={title}
                            className="flex gap-4 border-b border-rule p-5 last:border-b-0"
                        >
                            <div className="font-mono text-lg font-medium text-ink-muted">
                                {String(i + 1).padStart(2, '0')}
                            </div>
                            <div>
                                <div className="mb-1 text-item-title">
                                    {title}
                                </div>
                                <div className="text-body">{body}</div>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="mt-8 border border-rule-strong bg-surface-2 p-6">
                    <h2 className="mb-4 text-section-label">{prereq.title}</h2>
                    <ul className="list-disc space-y-2 pl-5 text-body">
                        {prereq.items.map((item) => (
                            <li key={item}>{item}</li>
                        ))}
                    </ul>
                </div>

                <div className="mt-12 border-t border-rule pt-8">
                    <h2 className="mb-3 text-section-title">
                        Et après le match ?
                    </h2>
                    <div className="max-w-lg space-y-4 text-2xl leading-relaxed text-ink">
                        <p>
                            Le matching n'est que le point de départ. Une fois
                            l'intérêt mutuel confirmé, les deux parties
                            disposent d'une infrastructure complète pour
                            conclure en toute confiance :
                        </p>
                        <ul className="list-disc space-y-2 pl-5">
                            <li>
                                <strong>Deal Room.</strong> Un espace dédié à la
                                négociation : documents, conditions du deal,
                                échanges suivis au même endroit.
                            </li>
                            <li>
                                <strong>KYC.</strong> Vérification d'identité
                                des deux parties avant toute opération
                                financière — documents stockés en privé, jamais
                                accessibles publiquement.
                            </li>
                            <li>
                                <strong>Contrat.</strong> Un contrat structuré
                                est généré à partir des conditions convenues
                                dans la Deal Room ; il reste un support de
                                travail qui n'exempte pas d'une validation
                                juridique humaine.
                            </li>
                            <li>
                                <strong>Séquestre (Escrow).</strong> Les fonds
                                de l'investisseur sont conservés sous séquestre
                                jusqu'à confirmation des deux parties, puis
                                libérés à l'entrepreneur — ou restitués si les
                                conditions ne sont pas remplies.
                            </li>
                        </ul>
                        <p className="text-xl text-ink-muted">
                            Certaines de ces étapes sont encore en cours de
                            déploiement : consultez la FAQ pour connaître l'état
                            exact de chaque fonctionnalité.
                        </p>
                    </div>
                </div>

                <div className="mt-12 flex flex-wrap gap-3">
                    {props.auth?.user ? (
                        <Link
                            href="/dashboard"
                            variant="primary"
                            className="px-6 py-3"
                        >
                            Aller à mon tableau de bord
                        </Link>
                    ) : (
                        <Button
                            type="button"
                            onClick={() => openRegister()}
                            variant="primary"
                            className="px-6 py-3"
                        >
                            Créer mon compte
                        </Button>
                    )}
                    {props.auth?.user ? (
                        !isEntrepreneur && (
                            <Link
                                href="/projects"
                                variant="outline"
                                className="px-6 py-3"
                            >
                                Découvrir les projets
                            </Link>
                        )
                    ) : (
                        <Button
                            type="button"
                            onClick={() => openLogin()}
                            variant="outline"
                            className="px-6 py-3"
                        >
                            Découvrir les projets
                        </Button>
                    )}
                </div>
            </div>
        </PublicLayout>
    );
}
