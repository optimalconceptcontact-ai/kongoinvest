import { router } from '@inertiajs/react';
import type { FormEvent} from 'react';
import { useState } from 'react';
import { SeoHead } from '@/components/SeoHead';
import { Input } from '@/components/Ui/Input';
import { PublicLayout } from '@/layouts/PublicLayout';

type FaqItem = {
    id: number;
    category: string;
    question: string;
    answer: string;
};

const categoryLabels: Record<string, string> = {
    General: 'Général',
    Entrepreneurs: 'Entrepreneurs',
    Investors: 'Investisseurs',
    Matching: 'Matching',
    Subscriptions: 'Abonnements',
    Payments: 'Paiements',
    Escrow: 'Escrow',
    Kyc: 'KYC',
    Contracts: 'Contrats',
    Security: 'Sécurité',
};

export default function Faq({
    faqs,
    categories,
    filters,
}: {
    faqs: FaqItem[];
    categories: string[];
    filters: { q?: string };
}) {
    const [q, setQ] = useState(filters.q ?? '');
    const [activeCategory, setActiveCategory] = useState<string | null>(null);

    function submit(e: FormEvent) {
        e.preventDefault();
        router.get('/faq', q ? { q } : {}, { preserveState: true });
    }

    const visible = activeCategory
        ? faqs.filter((f) => f.category === activeCategory)
        : faqs;
    const grouped = visible.reduce<Record<string, FaqItem[]>>((acc, f) => {
        (acc[f.category] ??= []).push(f);

        return acc;
    }, {});

    return (
        <PublicLayout>
            <SeoHead
                title="FAQ"
                description="Questions fréquentes sur KongoInvestment : entrepreneurs, investisseurs, matching, abonnements, paiements, Escrow, KYC, contrats et sécurité."
            />
            <div className="mx-auto max-w-3xl px-6 py-20">
                <p className="text-eyebrow">FAQ</p>
                <h1 className="mt-2 mb-4 text-page-title-lg">
                    Questions fréquentes.
                </h1>
                <p className="mb-8 max-w-2xl text-lg leading-relaxed text-slate-500">
                    Tout ce qu'il faut savoir sur le fonctionnement de
                    KongoInvestment : comptes et profils, publication de
                    projets, moteur de matching, abonnements, paiements,
                    séquestre (Escrow), KYC, contrats et sécurité. Utilisez la
                    recherche pour retrouver une question précise, ou filtrez
                    par catégorie pour explorer un thème en particulier.
                </p>

                <form onSubmit={submit} className="mb-6">
                    <Input
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Rechercher dans la FAQ..."
                        className="px-4 py-3"
                    />
                </form>

                <div className="mb-10 flex flex-wrap gap-2">
                    <button
                        onClick={() => setActiveCategory(null)}
                        className={`border px-3 py-1.5 text-base font-semibold ${!activeCategory ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-400 text-slate-700'}`}
                    >
                        Tout
                    </button>
                    {categories.map((c) => (
                        <button
                            key={c}
                            onClick={() => setActiveCategory(c)}
                            className={`border px-3 py-1.5 text-base font-semibold ${activeCategory === c ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-400 text-slate-700'}`}
                        >
                            {categoryLabels[c] ?? c}
                        </button>
                    ))}
                </div>

                {visible.length === 0 ? (
                    <div className="border border-dashed border-slate-400 py-16 text-center text-body">
                        <p className="mb-2 font-semibold text-slate-700">
                            Aucun résultat pour cette recherche.
                        </p>
                        <p className="mx-auto max-w-sm text-caption">
                            Essayez avec d'autres mots-clés, ou réinitialisez le
                            filtre de catégorie pour élargir votre recherche à
                            toute la FAQ.
                        </p>
                    </div>
                ) : (
                    Object.entries(grouped).map(([category, items]) => (
                        <div key={category} className="mb-10">
                            <h2 className="mb-3 font-mono text-lg tracking-widest text-slate-400 uppercase">
                                {categoryLabels[category] ?? category}
                            </h2>
                            {items.map((faq) => (
                                <details
                                    key={faq.id}
                                    className="border-b border-slate-300"
                                >
                                    <summary className="flex cursor-pointer list-none items-center justify-between py-4 text-btn text-slate-900">
                                        {faq.question}
                                        <span className="font-mono text-orange-500">
                                            +
                                        </span>
                                    </summary>
                                    <p className="max-w-xl pb-4 text-body">
                                        {faq.answer}
                                    </p>
                                </details>
                            ))}
                        </div>
                    ))
                )}
            </div>
        </PublicLayout>
    );
}
