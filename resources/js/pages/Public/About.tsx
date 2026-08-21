import { SeoHead } from '@/components/SeoHead';
import { PublicLayout } from '@/layouts/PublicLayout';

function Block({ title, body }: { title: string; body: string }) {
    return (
        <div className="border-t border-slate-200 py-8">
            <h2 className="mb-3 text-xl font-extrabold text-slate-900">{title}</h2>
            <p className="max-w-2xl text-sm leading-relaxed text-slate-600">{body}</p>
        </div>
    );
}

export default function About() {
    return (
        <PublicLayout>
            <SeoHead
                title="À propos"
                description="La mission de Kanda : connecter les entrepreneurs de la RDC à des investisseurs qualifiés grâce à un matching transparent et explicable."
            />
            <div className="mx-auto max-w-3xl px-6 py-16">
                <p className="text-eyebrow">À propos</p>
                <h1 className="mt-2 mb-10 text-page-title-lg">Pourquoi Kanda existe.</h1>

                <Block
                    title="Mission"
                    body="Donner aux entrepreneurs de la République Démocratique du Congo un accès direct à des investisseurs qualifiés du monde entier, à travers un processus de mise en relation transparent, mesurable et explicable — jamais une boîte noire."
                />
                <Block
                    title="Le problème"
                    body="Des entrepreneurs solides existent partout en RDC, mais l'accès aux capitaux reste structurellement difficile : réseaux fermés, information dispersée, absence d'outils pour évaluer objectivement la compatibilité entre un projet et un investisseur."
                />
                <Block
                    title="Notre approche"
                    body="Un moteur de matching hybride — règles métier objectives et analyse sémantique par IA — calcule un score de compatibilité entre chaque projet publié et chaque profil investisseur, avec le détail du calcul toujours visible, jamais une note brute."
                />
                <Block
                    title="Pourquoi la RDC"
                    body="La RDC concentre un potentiel entrepreneurial considérable et des besoins de financement réels dans l'agriculture, l'énergie, la santé et la technologie. Kanda choisit d'y ancrer structurellement sa base d'entrepreneurs, tout en ouvrant le capital à des investisseurs internationaux."
                />
                <Block
                    title="Sécurité et rigueur"
                    body="Chaque fonctionnalité financière — abonnements, futur système d'Escrow, KYC — est conçue avec la rigueur attendue d'une plateforme FinTech : autorisation systématique côté serveur, traçabilité des actions sensibles, et aucune donnée financière représentée en nombre à virgule flottante."
                />
            </div>
        </PublicLayout>
    );
}
