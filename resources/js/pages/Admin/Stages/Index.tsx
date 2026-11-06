import { Head, router, useForm } from '@inertiajs/react';
import {
    RiCloseLine,
    RiDeleteBinLine,
    RiFileLine,
    RiFlag2Line,
    RiPagesLine,
    RiPencilLine,
} from '@remixicon/react';
import { FormEvent, useState } from 'react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field } from '@/components/ui/Field';
import { FieldError } from '@/components/ui/FieldError';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/Label';
import { useConfirm } from '@/hooks/useConfirm';

type Stage = {
    id: number;
    key: string;
    name: string;
    description: string | null;
    is_active: boolean;
    order: number;
    projects_count: number;
};

function StageForm({ stage, onDone }: { stage?: Stage; onDone: () => void }) {
    const { data, setData, post, put, processing, errors } = useForm({
        key: stage?.key ?? '',
        name: stage?.name ?? '',
        description: stage?.description ?? '',
        is_active: stage?.is_active ?? true,
        order: stage?.order ?? 0,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        if (stage) {
            put(`/admin/stages/${stage.id}`, { onSuccess: onDone });
        } else {
            post('/admin/stages', { onSuccess: onDone });
        }
    }

    return (
        <form
            onSubmit={submit}
            className="flex flex-wrap items-end gap-3 border border-rule bg-surface-2 p-4"
        >
            {!stage && (
                <Field className="w-36" error={!!errors.key}>
                    <Label>Clé</Label>
                    <Input
                        size="sm"
                        icon={<RiFileLine size={18} />}
                        value={data.key}
                        onChange={(e) => setData('key', e.target.value)}
                        placeholder="early_stage"
                        required
                    />
                    <FieldError>{errors.key}</FieldError>
                </Field>
            )}
            <Field className="w-48" error={!!errors.name}>
                <Label>Nom</Label>
                <Input
                    size="sm"
                    icon={<RiFlag2Line size={18} />}
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    required
                />
                <FieldError>{errors.name}</FieldError>
            </Field>
            <Field className="min-w-48 flex-1" error={!!errors.description}>
                <Label>Description</Label>
                <Input
                    size="sm"
                    icon={<RiPagesLine size={18} />}
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                />
                <FieldError>{errors.description}</FieldError>
            </Field>
            {stage && (
                <Checkbox
                    size="sm"
                    label="Actif"
                    checked={data.is_active}
                    onChange={(e) => setData('is_active', e.target.checked)}
                />
            )}
            <Button
                type="submit"
                disabled={processing}
                size="sm"
                className="px-4"
            >
                {stage ? 'Enregistrer' : 'Ajouter'}
            </Button>
            {stage && (
                <button
                    type="button"
                    onClick={onDone}
                    className="px-2 py-1.5 text-caption"
                    aria-label="Annuler la modification"
                >
                    <RiCloseLine size={16} />
                </button>
            )}
        </form>
    );
}

export default function Index({ stages }: { stages: Stage[] }) {
    const [editing, setEditing] = useState<number | null>(null);
    const { confirm, ConfirmDialog } = useConfirm();

    async function destroy(stage: Stage) {
        const description =
            stage.projects_count > 0
                ? `Supprimer ce stade ? ${stage.projects_count} projet(s) l'utilisent actuellement.`
                : 'Supprimer ce stade ?';

        if (
            await confirm({
                description,
                danger: true,
                confirmLabel: 'Supprimer',
            })
        ) {
            router.delete(`/admin/stages/${stage.id}`);
        }
    }

    return (
        <AdminLayout title="Stades">
            <Head title="Admin — Stades" />

            <div className="mb-6">
                <StageForm onDone={() => {}} />
            </div>

            <div className="border border-rule">
                {stages.map((stage) =>
                    editing === stage.id ? (
                        <div
                            key={stage.id}
                            className="border-b border-rule last:border-b-0"
                        >
                            <StageForm
                                stage={stage}
                                onDone={() => setEditing(null)}
                            />
                        </div>
                    ) : (
                        <div
                            key={stage.id}
                            className="flex items-center justify-between border-b border-rule p-4 last:border-b-0"
                        >
                            <div>
                                <div className="flex items-center gap-2">
                                    <span className="text-item-title">
                                        {stage.name}
                                    </span>
                                    <span className="font-mono text-lg text-ink-muted">
                                        {stage.key}
                                    </span>
                                    {!stage.is_active && (
                                        <span className="font-mono text-lg text-ink-muted uppercase">
                                            Inactif
                                        </span>
                                    )}
                                </div>
                                <div className="text-caption">
                                    {stage.projects_count} projet(s) ·{' '}
                                    {stage.description}
                                </div>
                            </div>
                            <div className="flex gap-2">
                                <button
                                    type="button"
                                    onClick={() => setEditing(stage.id)}
                                    aria-label={`Modifier ${stage.name}`}
                                    className="flex h-10 w-10 items-center justify-center border border-rule-strong hover:border-ink"
                                >
                                    <RiPencilLine size={14} />
                                </button>
                                <button
                                    type="button"
                                    onClick={() => destroy(stage)}
                                    aria-label={`Supprimer ${stage.name}`}
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
