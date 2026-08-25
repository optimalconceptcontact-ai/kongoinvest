import { Head, router, useForm } from '@inertiajs/react';
import { RiCloseLine, RiDeleteBinLine, RiPencilLine } from '@remixicon/react';
import { FormEvent, useState } from 'react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Button } from '@/components/Ui/Button';
import { Checkbox } from '@/components/Ui/Checkbox';
import { FieldError } from '@/components/Ui/FieldError';
import { Input } from '@/components/Ui/Input';
import { Label } from '@/components/Ui/Label';
import { useConfirm } from '@/hooks/useConfirm';

type Partner = { id: number; name: string; category: string | null; website: string | null; is_published: boolean; order: number };

function PartnerForm({ partner, onDone }: { partner?: Partner; onDone: () => void }) {
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
        <form onSubmit={submit} className="flex flex-wrap items-end gap-3 border border-slate-300 bg-slate-50 p-4">
            <div>
                <Label className="text-xs">Nom</Label>
                <Input size="sm" className="w-48" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                <FieldError>{errors.name}</FieldError>
            </div>
            <div>
                <Label className="text-xs">Catégorie</Label>
                <Input size="sm" className="w-40" value={data.category} onChange={(e) => setData('category', e.target.value)} placeholder="incubator, fund..." />
            </div>
            <div className="flex-1">
                <Label className="text-xs">Site web</Label>
                <Input size="sm" value={data.website} onChange={(e) => setData('website', e.target.value)} />
            </div>
            <Checkbox size="sm" label="Publié" checked={data.is_published} onChange={(e) => setData('is_published', e.target.checked)} />
            <Button type="submit" disabled={processing} size="sm" className="px-4">
                {partner ? 'Enregistrer' : 'Ajouter'}
            </Button>
            {partner && (
                <button type="button" onClick={onDone} className="px-2 py-1.5 text-caption" aria-label="Annuler la modification">
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
        if (await confirm({ description: `Supprimer le partenaire « ${partner.name} » ?`, danger: true, confirmLabel: 'Supprimer' })) {
            router.delete(`/admin/partners/${partner.id}`);
        }
    }

    return (
        <AdminLayout title="Partenaires">
            <Head title="Admin — Partenaires" />
            <p className="mb-6 border border-slate-300 bg-slate-50 px-4 py-3 text-caption">
                Un partenaire n'apparaît sur la page d'accueil qu'une fois « Publié » coché — n'y entrez que des
                partenariats réels (spec §65).
            </p>

            <div className="mb-6"><PartnerForm onDone={() => {}} /></div>

            <div className="border border-slate-300">
                {partners.map((partner) =>
                    editing === partner.id ? (
                        <div key={partner.id} className="border-b border-slate-300 last:border-b-0">
                            <PartnerForm partner={partner} onDone={() => setEditing(null)} />
                        </div>
                    ) : (
                        <div key={partner.id} className="flex items-center justify-between border-b border-slate-300 p-4 last:border-b-0">
                            <div>
                                <div className="flex items-center gap-2">
                                    <span className="text-item-title">{partner.name}</span>
                                    <span className={`font-mono text-[10px] uppercase ${partner.is_published ? 'text-blue-600' : 'text-slate-400'}`}>
                                        {partner.is_published ? 'Publié' : 'Non publié'}
                                    </span>
                                </div>
                                <div className="text-caption">{partner.category}</div>
                            </div>
                            <div className="flex gap-2">
                                <button onClick={() => setEditing(partner.id)} className="border border-slate-400 p-2 hover:border-slate-900"><RiPencilLine size={14} /></button>
                                <button onClick={() => destroy(partner)} className="border border-slate-400 p-2 text-orange-600 hover:border-orange-600"><RiDeleteBinLine size={14} /></button>
                            </div>
                        </div>
                    ),
                )}
            </div>
            {ConfirmDialog}
        </AdminLayout>
    );
}
