import { router } from '@inertiajs/react';
import { SeoHead } from '@/components/SeoHead';
import { FormEvent, useState } from 'react';
import { Input } from '@/components/Ui/Input';
import { PublicLayout } from '@/layouts/PublicLayout';

type FaqItem = { id: number; category: string; question: string; answer: string };

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

export default function Faq({ faqs, categories, filters }: { faqs: FaqItem[]; categories: string[]; filters: { q?: string } }) {
    const [q, setQ] = useState(filters.q ?? '');
    const [activeCategory, setActiveCategory] = useState<string | null>(null);

    function submit(e: FormEvent) {
        e.preventDefault();
        router.get('/faq', q ? { q } : {}, { preserveState: true });
    }

    const visible = activeCategory ? faqs.filter((f) => f.category === activeCategory) : faqs;
    const grouped = visible.reduce<Record<string, FaqItem[]>>((acc, f) => {
        (acc[f.category] ??= []).push(f);
        return acc;
    }, {});

    return (
        <PublicLayout>
            <SeoHead title="FAQ" description="Questions fréquentes sur Kanda : entrepreneurs, investisseurs, matching, abonnements, paiements, Escrow, KYC, contrats et sécurité." />
            <div className="mx-auto max-w-3xl px-6 py-16">
                <p className="text-eyebrow">FAQ</p>
                <h1 className="mt-2 mb-8 text-page-title-lg">Questions fréquentes.</h1>

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
                        className={`border px-3 py-1.5 text-xs font-medium ${!activeCategory ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 text-slate-600'}`}
                    >
                        Tout
                    </button>
                    {categories.map((c) => (
                        <button
                            key={c}
                            onClick={() => setActiveCategory(c)}
                            className={`border px-3 py-1.5 text-xs font-medium ${activeCategory === c ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 text-slate-600'}`}
                        >
                            {categoryLabels[c] ?? c}
                        </button>
                    ))}
                </div>

                {visible.length === 0 ? (
                    <div className="border border-dashed border-slate-300 py-16 text-center text-body">
                        Aucun résultat pour cette recherche.
                    </div>
                ) : (
                    Object.entries(grouped).map(([category, items]) => (
                        <div key={category} className="mb-10">
                            <h2 className="mb-3 font-mono text-xs uppercase tracking-widest text-slate-400">
                                {categoryLabels[category] ?? category}
                            </h2>
                            {items.map((faq) => (
                                <details key={faq.id} className="border-b border-slate-200">
                                    <summary className="flex cursor-pointer list-none items-center justify-between py-4 text-btn text-slate-900">
                                        {faq.question}
                                        <span className="font-mono text-orange-500">+</span>
                                    </summary>
                                    <p className="max-w-xl pb-4 text-body">{faq.answer}</p>
                                </details>
                            ))}
                        </div>
                    ))
                )}
            </div>
        </PublicLayout>
    );
}
