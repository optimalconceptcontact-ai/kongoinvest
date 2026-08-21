import { SeoHead } from '@/components/SeoHead';
import { useState } from 'react';
import { PublicLayout } from '@/layouts/PublicLayout';

const paths = {
    entrepreneur: [
        ['Créez votre compte', 'Inscription en tant qu\'entrepreneur — votre projet est automatiquement rattaché à la RDC.'],
        ['Décrivez votre projet', 'Modèle économique, marché, équipe, traction, besoin de financement, secteurs et stades.'],
        ['Publiez', 'Une fois les champs essentiels renseignés, votre projet devient visible aux investisseurs.'],
        ['Recevez vos matches', 'Le moteur de matching calcule un score avec chaque investisseur actif et vous montre le détail.'],
        ['Passez à l\'étape suivante', 'Deal Room, KYC, contrat et Escrow arrivent dans les phases suivantes de la plateforme.'],
    ],
    investor: [
        ['Créez votre compte', 'Inscription en tant qu\'investisseur — disponible depuis n\'importe quel pays.'],
        ['Définissez votre thèse', 'Secteurs et stades multiples, ticket min/max, appétence au risque, géographies préférées.'],
        ['Découvrez des projets', 'Parcourez les projets publiés, filtrés par secteur, stade ou mot-clé.'],
        ['Consultez vos matches', 'Chaque projet compatible reçoit un score expliqué critère par critère.'],
        ['Passez à l\'étape suivante', 'Connexion, Deal Room et investissement arrivent dans les phases suivantes.'],
    ],
} as const;

export default function HowItWorks() {
    const [tab, setTab] = useState<'entrepreneur' | 'investor'>('entrepreneur');

    return (
        <PublicLayout>
            <SeoHead title="Comment ça marche" description="Le parcours entrepreneur et investisseur sur Kanda, étape par étape, de l'inscription à l'investissement." />
            <div className="mx-auto max-w-3xl px-6 py-16">
                <p className="text-eyebrow">Comment ça marche</p>
                <h1 className="mt-2 mb-10 text-page-title-lg">Un parcours structuré, des deux côtés.</h1>

                <div className="mb-10 flex w-max border border-slate-900">
                    {(['entrepreneur', 'investor'] as const).map((key) => (
                        <button
                            key={key}
                            onClick={() => setTab(key)}
                            className={`px-5 py-2.5 font-mono text-xs ${
                                tab === key ? 'bg-slate-900 text-white' : 'bg-white text-slate-500'
                            } ${key === 'entrepreneur' ? 'border-r border-slate-900' : ''}`}
                        >
                            {key === 'entrepreneur' ? 'Entrepreneur' : 'Investisseur'}
                        </button>
                    ))}
                </div>

                <div className="border border-slate-200">
                    {paths[tab].map(([title, body], i) => (
                        <div key={title} className="flex gap-4 border-b border-slate-200 p-5 last:border-b-0">
                            <div className="font-mono text-sm font-bold text-orange-600">{String(i + 1).padStart(2, '0')}</div>
                            <div>
                                <div className="mb-1 text-item-title">{title}</div>
                                <div className="text-body">{body}</div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </PublicLayout>
    );
}
