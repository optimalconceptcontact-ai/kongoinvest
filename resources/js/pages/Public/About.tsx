import type { ReactNode } from 'react';
import { SeoHead } from '@/components/SeoHead';
import { PublicLayout } from '@/layouts/PublicLayout';

function Block({ title, children }: { title: string; children: ReactNode }) {
    return (
        <div className="border-t border-rule py-8">
            <h2 className="mb-3 text-section-title">{title}</h2>
            <div className="max-w-2xl space-y-4 text-xl leading-[1.6] text-ink-muted">
                {children}
            </div>
        </div>
    );
}

export default function About() {
    return (
        <PublicLayout>
            <SeoHead
                title="À propos"
                description="La mission de KongoInvest : connecter les entrepreneurs de la RDC à des investisseurs qualifiés grâce à un matching transparent et explicable."
            />
            <div className="mx-auto max-w-3xl px-4 py-16 md:px-6">
                <p className="text-eyebrow">À propos</p>
                <h1 className="mt-2 mb-6 text-page-title-lg">
                    Pourquoi KongoInvest existe.
                </h1>
                <p className="mb-10 max-w-[620px] text-xl leading-[1.6] text-ink-muted">
                    KongoInvest est née d'un constat simple : le capital
                    existe, les projets existent, mais les deux mondes ne se
                    rencontrent presque jamais. Cette page explique ce que nous
                    construisons, comment nous le construisons, et pourquoi nous
                    avons choisi la RDC comme point d'ancrage.
                </p>

                <Block title="Mission">
                    <p>
                        Donner aux entrepreneurs de la République Démocratique
                        du Congo un accès direct à des investisseurs qualifiés
                        du monde entier, sans intermédiaire opaque et sans
                        dépendre de réseaux personnels. Concrètement, cela
                        signifie qu'un entrepreneur à Kinshasa, Lubumbashi ou
                        Goma peut présenter son projet à un investisseur basé à
                        Bruxelles, Dubaï ou Johannesburg, et être évalué sur la
                        qualité objective de son projet plutôt que sur la force
                        de son carnet d'adresses.
                    </p>
                    <p>
                        Le second pilier de notre mission est la transparence :
                        chaque mise en relation est mesurable et explicable.
                        Quand un investisseur voit votre projet, il sait
                        pourquoi ce projet lui est proposé ; quand vous
                        consultez vos matches, vous voyez le détail du calcul,
                        critère par critère. Jamais une boîte noire.
                    </p>
                </Block>

                <Block title="Le problème">
                    <p>
                        Des entrepreneurs solides existent partout en RDC, mais
                        l'accès aux capitaux reste structurellement difficile.
                        Ce n'est pas un problème de talent ni d'opportunités —
                        c'est un problème d'infrastructure de rencontre entre
                        l'offre et la demande de capital. Trois obstacles
                        reviennent systématiquement :
                    </p>
                    <ul className="list-disc space-y-2 pl-5">
                        <li>
                            <strong>Réseaux fermés.</strong> Les financements
                            circulent d'abord par relations personnelles ; ceux
                            qui sont hors du réseau n'entrent jamais dans la
                            conversation.
                        </li>
                        <li>
                            <strong>Information dispersée.</strong> Il n'existe
                            aucun endroit central où un investisseur peut
                            découvrir, filtrer et comparer des projets congolais
                            structurés.
                        </li>
                        <li>
                            <strong>Absence d'outils d'évaluation.</strong> Rien
                            ne permet aujourd'hui d'évaluer objectivement la
                            compatibilité entre un projet et un investisseur
                            avant d'engager des semaines de discussions.
                        </li>
                    </ul>
                </Block>

                <Block title="Notre approche">
                    <p>
                        Nous avons construit un moteur de matching hybride qui
                        combine deux familles d'analyse. D'un côté, des règles
                        métier objectives et déterministes : secteur d'activité,
                        stade du projet, fourchette de financement, géographie,
                        appétence au risque. De l'autre, une analyse sémantique
                        assistée par IA sur les dimensions plus qualitatives :
                        modèle économique, traction et impact.
                    </p>
                    <p>
                        Le résultat est un score de compatibilité de 0 à 100
                        entre chaque projet publié et chaque profil
                        investisseur. Ce qui fait la différence, c'est que le
                        détail du calcul est toujours visible : vous voyez
                        combien chaque critère pèse et pourquoi le score final
                        est ce qu'il est — jamais une note brute sortie d'un
                        algorithme muet. Et si l'analyse IA n'est pas
                        disponible, le calcul continue grâce à une estimation de
                        secours : une panne ne prive jamais personne de ses
                        matches.
                    </p>
                </Block>

                <Block title="Pourquoi la RDC">
                    <p>
                        La RDC concentre un potentiel entrepreneurial
                        considérable et des besoins de financement réels,
                        notamment dans l'agriculture, l'énergie, la santé et la
                        technologie — des secteurs où les projets créent à la
                        fois de la valeur économique et un impact social direct.
                        C'est un marché immense, jeune et dynamique, encore très
                        mal desservi par les circuits classiques
                        d'investissement.
                    </p>
                    <p>
                        KongoInvest choisit d'y ancrer structurellement sa
                        base d'entrepreneurs : c'est une règle fondamentale de
                        la plateforme, inscrite jusque dans la base de données —
                        un projet entrepreneurial ne peut pas exister hors de la
                        RDC. En face, le capital s'ouvre au monde entier : tout
                        investisseur, quel que soit son pays, peut créer un
                        compte, définir sa thèse d'investissement et découvrir
                        les projets.
                    </p>
                </Block>

                <Block title="Sécurité et rigueur">
                    <p>
                        Chaque fonctionnalité financière — abonnements, système
                        d'Escrow, KYC — est conçue avec la rigueur attendue
                        d'une plateforme FinTech. En pratique, cela veut dire
                        trois choses. D'abord, l'autorisation est
                        systématiquement vérifiée côté serveur : ce que
                        l'interface masque, le backend l'interdit aussi.
                        Ensuite, les actions sensibles sont tracées : chaque
                        opération importante laisse une piste d'audit
                        vérifiable.
                    </p>
                    <p>
                        Enfin, aucune donnée financière n'est représentée en
                        nombre à virgule flottante : tous les montants sont
                        stockés et calculés en unités entières (centimes), ce
                        qui élimine toute erreur d'arrondi dans les paiements.
                        Les documents d'identité fournis pour le KYC restent
                        privés, stockés hors d'accès public et consultables
                        uniquement par les personnes autorisées.
                    </p>
                </Block>

                <Block title="Notre engagement">
                    <p>
                        Nous préférons afficher moins de contenu que d'en
                        fabriquer : les partenaires et les témoignages publiés
                        sur ce site sont réels ou absents, jamais complétés par
                        des placeholders. La même exigence guidera la suite :
                        Deal Room structurée, séquestre (Escrow) pour sécuriser
                        les fonds, contrats générés à partir des conditions du
                        deal — toujours avec validation humaine, car une
                        plateforme de confiance ne remplace pas le jugement des
                        parties, elle le outille.
                    </p>
                </Block>
            </div>
        </PublicLayout>
    );
}
