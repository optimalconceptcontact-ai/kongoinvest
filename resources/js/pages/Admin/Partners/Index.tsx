import { Head, router, useForm } from '@inertiajs/react';
import {
    RiBriefcaseLine,
    RiCloseLine,
    RiDeleteBinLine,
    RiGlobalLine,
    RiPagesLine,
    RiPencilLine,
    RiPriceTagLine,
} from '@remixicon/react';
import { FormEvent, useState } from 'react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field } from '@/components/ui/Field';
import { FieldError } from '@/components/ui/FieldError';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/Label';
import { UrlInput } from '@/components/ui/UrlInput';
import { useConfirm } from '@/hooks/useConfirm';

type Partner = {
    id: number;
    name: string;
    category: string | null;
    website: string | null;
    is_published: boolean;
    order: number;
};

function PartnerForm({
    partner,
    onDone,
}: {
    partner?: Partner;
    onDone: () => void;
}) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: partner?.name ?? '',
        category: partner?.category ?? '',
        website: partner?.website ?? '',
        is_published: partner?.is_published ?? false,
        order: partner?.order ?? 0,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        if (partner) {
            put(`/admin/partners/${partner.id}`, { onSuccess: onDone });
        } else {
            post('/admin/partners', { onSuccess: onDone });
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
                    icon={<RiBriefcaseLine size={18} />}
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    required
                />
                <FieldError>{errors.name}</FieldError>
            </Field>
            <Field className="w-40" error={!!errors.category}>
                <Label>Catégorie</Label>
                <Input
                    size="sm"
                    icon={<RiPriceTagLine size={18} />}
                    value={data.category}
                    onChange={(e) => setData('category', e.target.value)}
                    placeholder="incubateur, fonds..."
                />
                <FieldError>{errors.category}</FieldError>
            </Field>
            <Field className="min-w-48 flex-1" error={!!errors.website}>
                <Label>Site web</Label>
                <UrlInput
                    size="sm"
                    icon={<RiGlobalLine size={18} />}
                    value={data.website}
                    onChange={(website) => setData('website', website)}
                />
                <FieldError>{errors.website}</FieldError>
            </Field>
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
                {partner ? 'Enregistrer' : 'Ajouter'}
            </Button>
            {partner && (
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

export default function Index({ partners }: { partners: Partner[] }) {
    const [editing, setEditing] = useState<number | null>(null);
    const { confirm, ConfirmDialog } = useConfirm();

    async function destroy(partner: Partner) {
        if (
            await confirm({
                description: `Supprimer le partenaire « ${partner.name} » ?`,
                danger: true,
                confirmLabel: 'Supprimer',
            })
        ) {
            router.delete(`/admin/partners/${partner.id}`);
        }
    }

    return (
        <AdminLayout title="Partenaires">
            <Head title="Admin — Partenaires" />
            <p className="mb-6 border border-rule bg-surface-2 px-4 py-3 text-caption">
                Un partenaire n'apparaît sur la page d'accueil qu'une fois «
                Publié » coché. N'y entrez que des partenariats réels.
            </p>

            <div className="mb-6">
                <PartnerForm onDone={() => {}} />
            </div>

            <div className="border border-rule">
                {partners.map((partner) =>
                    editing === partner.id ? (
                        <div
                            key={partner.id}
                            className="border-b border-rule last:border-b-0"
                        >
                            <PartnerForm
                                partner={partner}
                                onDone={() => setEditing(null)}
                            />
                        </div>
                    ) : (
                        <div
                            key={partner.id}
                            className="flex items-center justify-between border-b border-rule p-4 last:border-b-0"
                        >
                            <div>
                                <div className="flex items-center gap-2">
                                    <span className="text-item-title">
                                        {partner.name}
                                    </span>
                                    <span
                                        className={`font-mono text-lg uppercase ${partner.is_published ? 'text-action' : 'text-ink-muted'}`}
                                    >
                                        {partner.is_published
                                            ? 'Publié'
                                            : 'Non publié'}
                                    </span>
                                </div>
                                <div className="text-caption">
                                    {partner.category}
                                </div>
                            </div>
                            <div className="flex gap-2">
                                <button
                                    type="button"
                                    onClick={() => setEditing(partner.id)}
                                    aria-label={`Modifier ${partner.name}`}
                                    className="flex h-10 w-10 items-center justify-center border border-rule-strong hover:border-ink"
                                >
                                    <RiPencilLine size={14} />
                                </button>
                                <button
                                    type="button"
                                    onClick={() => destroy(partner)}
                                    aria-label={`Supprimer ${partner.name}`}
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
