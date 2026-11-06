import { Head, router, useForm } from '@inertiajs/react';
import {
    RiCloseLine,
    RiDeleteBinLine,
    RiPagesLine,
    RiPencilLine,
    RiRadarLine,
    RiShape2Line,
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

type Sector = {
    id: number;
    name: string;
    icon: string | null;
    description: string | null;
    is_active: boolean;
    order: number;
    projects_count: number;
};

function SectorForm({
    sector,
    onDone,
}: {
    sector?: Sector;
    onDone: () => void;
}) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: sector?.name ?? '',
        icon: sector?.icon ?? '',
        description: sector?.description ?? '',
        is_active: sector?.is_active ?? true,
        order: sector?.order ?? 0,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        if (sector) {
            put(`/admin/sectors/${sector.id}`, { onSuccess: onDone });
        } else {
            post('/admin/sectors', { onSuccess: onDone });
        }
    }

    return (
        <form
            onSubmit={submit}
            className="flex flex-wrap items-end gap-3 border border-rule bg-surface-2 p-4"
        >
            <Field className="w-48" error={!!errors.name}>
                <Label>Nom</Label>
                <Input
                    size="sm"
                    icon={<RiRadarLine size={18} />}
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    required
                />
                <FieldError>{errors.name}</FieldError>
            </Field>
            <Field className="w-36" error={!!errors.icon}>
                <Label>Icône (RemixIcon)</Label>
                <Input
                    size="sm"
                    icon={<RiShape2Line size={18} />}
                    value={data.icon}
                    onChange={(e) => setData('icon', e.target.value)}
                    placeholder="RiPlantLine"
                />
                <FieldError>{errors.icon}</FieldError>
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
            {sector && (
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
                {sector ? 'Enregistrer' : 'Ajouter'}
            </Button>
            {sector && (
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

export default function Index({ sectors }: { sectors: Sector[] }) {
    const [editing, setEditing] = useState<number | null>(null);
    const { confirm, ConfirmDialog } = useConfirm();

    async function destroy(sector: Sector) {
        const description =
            sector.projects_count > 0
                ? `Supprimer ce secteur ? ${sector.projects_count} projet(s) l'utilisent actuellement.`
                : 'Supprimer ce secteur ?';

        if (
            await confirm({
                description,
                danger: true,
                confirmLabel: 'Supprimer',
            })
        ) {
            router.delete(`/admin/sectors/${sector.id}`);
        }
    }

    return (
        <AdminLayout title="Secteurs">
            <Head title="Admin — Secteurs" />

            <div className="mb-6">
                <SectorForm onDone={() => {}} />
            </div>

            <div className="border border-rule">
                {sectors.map((sector) =>
                    editing === sector.id ? (
                        <div
                            key={sector.id}
                            className="border-b border-rule last:border-b-0"
                        >
                            <SectorForm
                                sector={sector}
                                onDone={() => setEditing(null)}
                            />
                        </div>
                    ) : (
                        <div
                            key={sector.id}
                            className="flex items-center justify-between border-b border-rule p-4 last:border-b-0"
                        >
                            <div>
                                <div className="flex items-center gap-2">
                                    <span className="text-item-title">
                                        {sector.name}
                                    </span>
                                    {!sector.is_active && (
                                        <span className="font-mono text-lg text-ink-muted uppercase">
                                            Inactif
                                        </span>
                                    )}
                                </div>
                                <div className="text-caption">
                                    {sector.projects_count} projet(s) ·{' '}
                                    {sector.description}
                                </div>
                            </div>
                            <div className="flex gap-2">
                                <button
                                    type="button"
                                    onClick={() => setEditing(sector.id)}
                                    aria-label={`Modifier ${sector.name}`}
                                    className="flex h-10 w-10 items-center justify-center border border-rule-strong hover:border-ink"
                                >
                                    <RiPencilLine size={14} />
                                </button>
                                <button
                                    type="button"
                                    onClick={() => destroy(sector)}
                                    aria-label={`Supprimer ${sector.name}`}
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
