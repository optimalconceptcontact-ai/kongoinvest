import { router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { RiSearchLine } from '@remixicon/react';
import { SeoHead } from '@/components/SeoHead';
import { Input } from '@/components/ui/input';
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
                description="Questions fréquentes sur KongoInvest : entrepreneurs, investisseurs, matching, abonnements, paiements, Escrow, KYC, contrats et sécurité."
            />
            <div className="mx-auto max-w-3xl px-4 py-16 md:px-6">
                <p className="text-eyebrow">FAQ</p>
                <h1 className="mt-2 mb-4 text-page-title-lg">
                    Questions fréquentes.
                </h1>
                <p className="mb-8 max-w-2xl text-xl leading-7 text-ink-muted">
                    Tout ce qu'il faut savoir sur le fonctionnement de
                    KongoInvest : comptes et profils, publication de
                    projets, moteur de matching, abonnements, paiements,
                    séquestre (Escrow), KYC, contrats et sécurité. Utilisez la
                    recherche pour retrouver une question précise, ou filtrez
                    par catégorie pour explorer un thème en particulier.
                </p>

                <form onSubmit={submit} className="mb-6">
                    <Input
                        icon={<RiSearchLine size={18} />}
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        placeholder="Rechercher dans la FAQ..."
                        className="px-4 py-3"
                    />
                </form>

                <div className="mb-10 flex flex-wrap gap-2">
                    <button
                        onClick={() => setActiveCategory(null)}
                        className={`min-h-11 border px-3 py-1.5 text-btn ${!activeCategory ? 'border-ink bg-ink text-paper' : 'border-rule-strong text-ink hover:border-ink'}`}
                    >
                        Tout
                    </button>
                    {categories.map((c) => (
                        <button
                            key={c}
                            onClick={() => setActiveCategory(c)}
                            className={`min-h-11 border px-3 py-1.5 text-btn ${activeCategory === c ? 'border-ink bg-ink text-paper' : 'border-rule-strong text-ink hover:border-ink'}`}
                        >
                            {categoryLabels[c] ?? c}
                        </button>
                    ))}
                </div>

                {visible.length === 0 ? (
                    <div className="border border-rule-strong bg-paper p-6 text-left text-body">
                        <p className="mb-2 font-semibold text-ink">
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
                            <h2 className="mb-3 text-section-label">
                                {categoryLabels[category] ?? category}
                            </h2>
                            {items.map((faq) => (
                                <details
                                    key={faq.id}
                                    className="border-b border-rule"
                                >
                                    
                                    <summary className="flex min-h-12 cursor-pointer list-none items-center justify-between py-5 text-item-title select-none">
                                        <span className="pr-4">
                                            {faq.question}
                                        </span>
                                        <span className="font-mono text-2xl font-semibold text-ink transition-transform duration-200 group-open:rotate-45">
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
