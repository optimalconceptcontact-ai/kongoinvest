import { Head, router, useForm } from '@inertiajs/react';
import {
    RiArticleLine,
    RiDeleteBinLine,
    RiPencilLine,
    RiQuillPenLine,
} from '@remixicon/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field } from '@/components/ui/Field';
import { FieldError } from '@/components/ui/FieldError';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/Label';
import { RichTextEditor } from '@/components/ui/RichTextEditor';
import { Select } from '@/components/ui/select';
import { Textarea } from '@/components/ui/Textarea';
import { useConfirm } from '@/hooks/useConfirm';
import { AdminLayout } from '@/layouts/AdminLayout';

type GuideItem = {
    id: number;
    section: string;
    title: string;
    description: string | null;
    content: string;
    order: number;
    is_published: boolean;
};

type Section = { key: string; label: string };

function GuideForm({
    guide,
    sections,
    onDone,
}: {
    guide?: GuideItem;
    sections: Section[];
    onDone: () => void;
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm({
        section: guide?.section ?? sections[0]?.key ?? 'ohada-compliance',
        title: guide?.title ?? '',
        description: guide?.description ?? '',
        content: guide?.content ?? '',
        order: guide?.order ?? 0,
        is_published: guide?.is_published ?? true,
    });

    function submit(e: FormEvent) {
        e.preventDefault();

        if (guide) {
            put(`/admin/guides/${guide.id}`, { onSuccess: onDone });
        } else {
            post('/admin/guides', {
                onSuccess: () => {
                    reset();
                    onDone();
                },
            });
        }
    }

    return (
        <form
            onSubmit={submit}
            className="flex flex-col gap-4 border border-rule bg-surface-2 p-4"
        >
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <Field error={!!errors.section}>
                    <Label>Section du guide</Label>
                    <Select
                        value={data.section}
                        onChange={(e) => setData('section', e.target.value)}
                        error={!!errors.section}
                    >
                        {sections.map((section) => (
                            <option key={section.key} value={section.key}>
                                {section.label}
                            </option>
                        ))}
                    </Select>
                    <FieldError>{errors.section}</FieldError>
                </Field>

                <Field error={!!errors.order}>
                    <Label>Ordre d'affichage</Label>
                    <Input
                        type="number"
                        min={0}
                        value={data.order}
                        onChange={(e) =>
                            setData('order', Number(e.target.value) || 0)
                        }
                        error={!!errors.order}
                    />
                    <FieldError>{errors.order}</FieldError>
                </Field>
            </div>

            <Field error={!!errors.title}>
                <Label>Titre de l'article</Label>
                <Input
                    icon={<RiArticleLine size={18} />}
                    value={data.title}
                    onChange={(e) => setData('title', e.target.value)}
                    placeholder="Ex : Qu'est-ce que le droit OHADA ?"
                    error={!!errors.title}
                />
                <FieldError>{errors.title}</FieldError>
            </Field>

            <Field error={!!errors.description}>
                <Label>Description / chapô</Label>
                <Textarea
                    size="sm"
                    rows={3}
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    placeholder="Résumé introductif affiché sous le titre, en grand texte."
                    error={!!errors.description}
                />
                <FieldError>{errors.description}</FieldError>
            </Field>

            <div>
                <p className="text-form-label">
                    Contenu de l'article
                    <span aria-hidden className="ml-1 text-danger">
                        *
                    </span>
                </p>
                <RichTextEditor
                    value={data.content}
                    onChange={(html) => setData('content', html)}
                    error={!!errors.content}
                />
                <FieldError>{errors.content}</FieldError>
            </div>

            <div className="flex flex-wrap items-center gap-3">
                <Checkbox
                    size="sm"
                    label="Publié"
                    checked={data.is_published}
                    onChange={(e) => setData('is_published', e.target.checked)}
                />
                <Button type="submit" disabled={processing} size="sm">
                    {guide ? 'Enregistrer' : 'Ajouter l’article'}
                </Button>
                {guide && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={onDone}
                    >
                        Annuler
                    </Button>
                )}
            </div>
        </form>
    );
}

export default function Index({
    guides,
    sections,
}: {
    guides: GuideItem[];
    sections: Section[];
}) {
    const [editing, setEditing] = useState<number | null>(null);
    const { confirm, ConfirmDialog } = useConfirm();

    function sectionLabel(key: string) {
        return sections.find((s) => s.key === key)?.label ?? key;
    }

    async function destroy(guide: GuideItem) {
        if (
            await confirm({
                description: `Supprimer l'article « ${guide.title} » ?`,
                danger: true,
                confirmLabel: 'Supprimer',
            })
        ) {
            router.delete(`/admin/guides/${guide.id}`);
        }
    }

    return (
        <AdminLayout title="Guide">
            <Head title="Admin — Guide" />

            <div className="mb-6">
                <p className="mb-4 max-w-2xl text-body">
                    Les articles rédigés ici sont publiés dans le guide public,
                    sous l'onglet de leur section. Le contenu est mis en forme
                    en texte enrichi et s'affiche comme un véritable article.
                </p>
                <GuideForm
                    sections={sections}
                    onDone={() => setEditing(null)}
                />
            </div>

            <div className="border border-rule">
                {guides.length === 0 && (
                    <div className="flex items-center gap-3 p-6 text-caption">
                        <RiQuillPenLine size={20} />
                        Aucun article pour l'instant — rédigez le premier
                        ci-dessus.
                    </div>
                )}
                {guides.map((guide) =>
                    editing === guide.id ? (
                        <div
                            key={guide.id}
                            className="border-b border-rule last:border-b-0"
                        >
                            <GuideForm
                                guide={guide}
                                sections={sections}
                                onDone={() => setEditing(null)}
                            />
                        </div>
                    ) : (
                        <div
                            key={guide.id}
                            className="flex items-start justify-between gap-4 border-b border-rule p-4 last:border-b-0"
                        >
                            <div className="min-w-0">
                                <div className="mb-1 flex flex-wrap items-center gap-2">
                                    <span className="font-mono text-sm text-action uppercase">
                                        {sectionLabel(guide.section)}
                                    </span>
                                    {!guide.is_published && (
                                        <span className="border border-rule bg-surface-2 px-1.5 py-0.5 font-mono text-xs text-ink-muted uppercase">
                                            Non publié
                                        </span>
                                    )}
                                    <span className="font-mono text-xs text-ink-muted">
                                        ordre {guide.order}
                                    </span>
                                </div>
                                <div className="text-item-title">
                                    {guide.title}
                                </div>
                                {guide.description && (
                                    <p className="mt-1 line-clamp-2 max-w-2xl text-caption">
                                        {guide.description}
                                    </p>
                                )}
                            </div>
                            <div className="flex shrink-0 gap-2">
                                <button
                                    type="button"
                                    onClick={() => setEditing(guide.id)}
                                    aria-label={`Modifier ${guide.title}`}
                                    className="flex h-10 w-10 items-center justify-center border border-rule-strong hover:border-ink"
                                >
                                    <RiPencilLine size={14} />
                                </button>
                                <button
                                    type="button"
                                    onClick={() => destroy(guide)}
                                    aria-label={`Supprimer ${guide.title}`}
                                    className="flex h-10 w-10 items-center justify-center border border-rule-strong text-danger hover:border-danger"
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
