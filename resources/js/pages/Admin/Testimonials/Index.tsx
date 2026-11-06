import { Head, router, useForm } from '@inertiajs/react';
import {
    RiBriefcaseLine,
    RiBuildingLine,
    RiCloseLine,
    RiDeleteBinLine,
    RiPencilLine,
    RiUserLine,
} from '@remixicon/react';
import { FormEvent, useState } from 'react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field } from '@/components/ui/Field';
import { FieldError } from '@/components/ui/FieldError';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/Label';
import { Textarea } from '@/components/ui/Textarea';
import { useConfirm } from '@/hooks/useConfirm';

type Testimonial = {
    id: number;
    name: string;
    role: string | null;
    company: string | null;
    content: string;
    is_published: boolean;
};

function TestimonialForm({
    testimonial,
    onDone,
}: {
    testimonial?: Testimonial;
    onDone: () => void;
}) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: testimonial?.name ?? '',
        role: testimonial?.role ?? '',
        company: testimonial?.company ?? '',
        content: testimonial?.content ?? '',
        is_published: testimonial?.is_published ?? false,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        if (testimonial) {
            put(`/admin/testimonials/${testimonial.id}`, { onSuccess: onDone });
        } else {
            post('/admin/testimonials', { onSuccess: onDone });
        }
    }

    return (
        <form
            onSubmit={submit}
            className="flex flex-col gap-3 border border-rule bg-surface-2 p-4"
        >
            <div className="flex flex-wrap gap-3">
                <Field className="w-48" error={!!errors.name}>
                    <Label>Nom</Label>
                    <Input
                        size="sm"
                        icon={<RiUserLine size={18} />}
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                    />
                    <FieldError>{errors.name}</FieldError>
                </Field>
                <Field className="w-40" error={!!errors.role}>
                    <Label>Rôle</Label>
                    <Input
                        size="sm"
                        icon={<RiBriefcaseLine size={18} />}
                        value={data.role}
                        onChange={(e) => setData('role', e.target.value)}
                    />
                    <FieldError>{errors.role}</FieldError>
                </Field>
                <Field className="w-40" error={!!errors.company}>
                    <Label>Société</Label>
                    <Input
                        size="sm"
                        icon={<RiBuildingLine size={18} />}
                        value={data.company}
                        onChange={(e) => setData('company', e.target.value)}
                    />
                    <FieldError>{errors.company}</FieldError>
                </Field>
            </div>
            <Field error={!!errors.content}>
                <Label>Témoignage (texte réel du contributeur)</Label>
                <Textarea
                    size="sm"
                    rows={3}
                    value={data.content}
                    onChange={(e) => setData('content', e.target.value)}
                    required
                />
                <FieldError>{errors.content}</FieldError>
            </Field>
            <div className="flex items-center gap-3">
                <Checkbox
                    size="sm"
                    label="Publié"
                    checked={data.is_published}
                    onChange={(e) => setData('is_published', e.target.checked)}
                />
                <Button
                    type="submit"
                    disabled={processing}
                    size="sm"
                    className="px-4"
                >
                    {testimonial ? 'Enregistrer' : 'Ajouter'}
                </Button>
                {testimonial && (
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

export default function Index({
    testimonials,
}: {
    testimonials: Testimonial[];
}) {
    const [editing, setEditing] = useState<number | null>(null);
    const { confirm, ConfirmDialog } = useConfirm();

    async function destroy(id: number) {
        if (
            await confirm({
                description: 'Supprimer ce témoignage ?',
                danger: true,
                confirmLabel: 'Supprimer',
            })
        ) {
            router.delete(`/admin/testimonials/${id}`);
        }
    }

    return (
        <AdminLayout title="Témoignages">
            <Head title="Admin — Témoignages" />
            <p className="text-form-error mb-6 border border-danger/30 bg-danger/10 px-4 py-3">
                N'entrez ici que les mots réels d'un utilisateur réel, avec son
                accord. Rien ici ne doit être inventé.
            </p>

            <div className="mb-6">
                <TestimonialForm onDone={() => {}} />
            </div>

            <div className="border border-rule">
                {testimonials.map((t) =>
                    editing === t.id ? (
                        <div
                            key={t.id}
                            className="border-b border-rule last:border-b-0"
                        >
                            <TestimonialForm
                                testimonial={t}
                                onDone={() => setEditing(null)}
                            />
                        </div>
                    ) : (
                        <div
                            key={t.id}
                            className="flex items-start justify-between border-b border-rule p-4 last:border-b-0"
                        >
                            <div>
                                <div className="flex items-center gap-2">
                                    <span className="text-item-title">
                                        {t.name}
                                    </span>
                                    <span
                                        className={`font-mono text-lg uppercase ${t.is_published ? 'text-action' : 'text-ink-muted'}`}
                                    >
                                        {t.is_published
                                            ? 'Publié'
                                            : 'Non publié'}
                                    </span>
                                </div>
                                <div className="mb-1 text-caption">
                                    {[t.role, t.company]
                                        .filter(Boolean)
                                        .join(', ')}
                                </div>
                                <p className="max-w-lg text-lg text-ink">
                                    "{t.content}"
                                </p>
                            </div>
                            <div className="flex gap-2">
                                <button
                                    type="button"
                                    onClick={() => setEditing(t.id)}
                                    aria-label={`Modifier le témoignage de ${t.name}`}
                                    className="flex h-10 w-10 items-center justify-center border border-rule-strong hover:border-ink"
                                >
                                    <RiPencilLine size={14} />
                                </button>
                                <button
                                    type="button"
                                    onClick={() => destroy(t.id)}
                                    aria-label={`Supprimer le témoignage de ${t.name}`}
                                    className="flex h-10 w-10 items-center justify-center border border-danger/60 text-danger hover:border-danger"
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
