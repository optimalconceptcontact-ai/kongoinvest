import { Head, router, useForm } from '@inertiajs/react';
import {
    RiCloseLine,
    RiDeleteBinLine,
    RiPencilLine,
    RiQuestionnaireLine,
} from '@remixicon/react';
import { FormEvent, useState } from 'react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { FieldError } from '@/components/ui/FieldError';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/Textarea';
import { useConfirm } from '@/hooks/useConfirm';

type FaqItem = {
    id: number;
    category: string;
    question: string;
    answer: string;
    is_published: boolean;
};

const categories = [
    'General',
    'Entrepreneurs',
    'Investors',
    'Matching',
    'Subscriptions',
    'Payments',
    'Escrow',
    'Kyc',
    'Contracts',
    'Security',
];

function FaqForm({ faq, onDone }: { faq?: FaqItem; onDone: () => void }) {
    const { data, setData, post, put, processing, errors } = useForm({
        category: faq?.category ?? categories[0],
        question: faq?.question ?? '',
        answer: faq?.answer ?? '',
        is_published: faq?.is_published ?? true,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        if (faq) {
            put(`/admin/faqs/${faq.id}`, { onSuccess: onDone });
        } else {
            post('/admin/faqs', { onSuccess: onDone });
        }
    }

    return (
        <form
            onSubmit={submit}
            className="flex flex-col gap-3 border border-rule bg-surface-2 p-4"
        >
            <div className="flex gap-3">
                <Select
                    size="sm"
                    className="w-auto text-lg"
                    value={data.category}
                    onChange={(e) => setData('category', e.target.value)}
                >
                    {categories.map((c) => (
                        <option key={c} value={c}>
                            {c}
                        </option>
                    ))}
                </Select>
                <Input
                    size="sm"
                    icon={<RiQuestionnaireLine size={18} />}
                    className="flex-1"
                    value={data.question}
                    onChange={(e) => setData('question', e.target.value)}
                    placeholder="Question"
                />
            </div>
            <FieldError>{errors.question}</FieldError>
            <Textarea
                size="sm"
                rows={2}
                value={data.answer}
                onChange={(e) => setData('answer', e.target.value)}
                placeholder="Réponse"
            />
            <div className="flex items-center gap-3">
                <Checkbox
                    size="sm"
                    label="Publiée"
                    checked={data.is_published}
                    onChange={(e) => setData('is_published', e.target.checked)}
                />
                <Button
                    type="submit"
                    disabled={processing}
                    size="sm"
                    className="px-4"
                >
                    {faq ? 'Enregistrer' : 'Ajouter'}
                </Button>
                {faq && (
                    <button
                        type="button"
                        onClick={onDone}
                        className="px-2 py-1.5 text-caption"
                        aria-label="Annuler la modification"
                    >
                        <RiCloseLine size={16} />
                    </button>
                )}
            </div>
        </form>
    );
}

export default function Index({ faqs }: { faqs: FaqItem[] }) {
    const [editing, setEditing] = useState<number | null>(null);
    const { confirm, ConfirmDialog } = useConfirm();

    async function destroy(faq: FaqItem) {
        if (
            await confirm({
                description: `Supprimer la question « ${faq.question} » ?`,
                danger: true,
                confirmLabel: 'Supprimer',
            })
        ) {
            router.delete(`/admin/faqs/${faq.id}`);
        }
    }

    return (
        <AdminLayout title="FAQ">
            <Head title="Admin — FAQ" />

            <div className="mb-6">
                <FaqForm onDone={() => {}} />
            </div>

            <div className="border border-rule">
                {faqs.map((faq) =>
                    editing === faq.id ? (
                        <div
                            key={faq.id}
                            className="border-b border-rule last:border-b-0"
                        >
                            <FaqForm
                                faq={faq}
                                onDone={() => setEditing(null)}
                            />
                        </div>
                    ) : (
                        <div
                            key={faq.id}
                            className="flex items-start justify-between border-b border-rule p-4 last:border-b-0"
                        >
                            <div>
                                <div className="mb-1 flex items-center gap-2">
                                    <span className="font-mono text-lg text-action uppercase">
                                        {faq.category}
                                    </span>
                                    {!faq.is_published && (
                                        <span className="font-mono text-lg text-ink-muted uppercase">
                                            Non publiée
                                        </span>
                                    )}
                                </div>
                                <div className="text-item-title">
                                    {faq.question}
                                </div>
                                <p className="max-w-lg text-caption">
                                    {faq.answer}
                                </p>
                            </div>
                            <div className="flex gap-2">
                                <button
                                    onClick={() => setEditing(faq.id)}
                                    className="border border-rule-strong p-2 hover:border-ink"
                                >
                                    <RiPencilLine size={14} />
                                </button>
                                <button
                                    onClick={() => destroy(faq)}
                                    className="border border-rule-strong p-2 text-action hover:border-action"
                                >
                                    <RiDeleteBinLine size={14} />
                                </button>
                            </div>
                        </div>
                    ),
                )}
            </div>
            {ConfirmDialog}
        </AdminLayout>
    );
}
