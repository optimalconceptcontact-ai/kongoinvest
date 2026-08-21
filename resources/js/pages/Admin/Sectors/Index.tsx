import { Head, router, useForm } from '@inertiajs/react';
import { RiCloseLine, RiDeleteBinLine, RiPencilLine } from '@remixicon/react';
import { FormEvent, useState } from 'react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Button } from '@/components/Ui/Button';
import { Checkbox } from '@/components/Ui/Checkbox';
import { FieldError } from '@/components/Ui/FieldError';
import { Input } from '@/components/Ui/Input';
import { Label } from '@/components/Ui/Label';

type Sector = { id: number; name: string; icon: string | null; description: string | null; is_active: boolean; order: number; projects_count: number };

function SectorForm({ sector, onDone }: { sector?: Sector; onDone: () => void }) {
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
        <form onSubmit={submit} className="flex flex-wrap items-end gap-3 border border-slate-200 bg-slate-50 p-4">
            <div>
                <Label className="text-xs">Nom</Label>
                <Input size="sm" className="w-48" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                <FieldError>{errors.name}</FieldError>
            </div>
            <div>
                <Label className="text-xs">Icône (RemixIcon)</Label>
                <Input size="sm" className="w-36" value={data.icon} onChange={(e) => setData('icon', e.target.value)} placeholder="RiPlantLine" />
            </div>
            <div className="flex-1">
                <Label className="text-xs">Description</Label>
                <Input size="sm" value={data.description} onChange={(e) => setData('description', e.target.value)} />
            </div>
            {sector && (
                <Checkbox size="sm" label="Actif" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} />
            )}
            <Button type="submit" disabled={processing} size="sm" className="px-4">
                {sector ? 'Enregistrer' : 'Ajouter'}
            </Button>
            {sector && (
                <button type="button" onClick={onDone} className="px-2 py-1.5 text-caption" aria-label="Annuler la modification">
                    <RiCloseLine size={16} />
                </button>
            )}
        </form>
    );
}

export default function Index({ sectors }: { sectors: Sector[] }) {
    const [editing, setEditing] = useState<number | null>(null);

    function destroy(id: number) {
        if (confirm('Supprimer ce secteur ?')) {
            router.delete(`/admin/sectors/${id}`);
        }
    }

    return (
        <AdminLayout title="Secteurs">
            <Head title="Admin — Secteurs" />

            <div className="mb-6">
                <SectorForm onDone={() => {}} />
            </div>

            <div className="border border-slate-200">
                {sectors.map((sector) =>
                    editing === sector.id ? (
                        <div key={sector.id} className="border-b border-slate-200 last:border-b-0">
                            <SectorForm sector={sector} onDone={() => setEditing(null)} />
                        </div>
                    ) : (
                        <div key={sector.id} className="flex items-center justify-between border-b border-slate-200 p-4 last:border-b-0">
                            <div>
                                <div className="flex items-center gap-2">
                                    <span className="text-item-title">{sector.name}</span>
                                    {!sector.is_active && <span className="font-mono text-[10px] uppercase text-slate-400">Inactif</span>}
                                </div>
                                <div className="text-caption">{sector.projects_count} projet(s) · {sector.description}</div>
                            </div>
                            <div className="flex gap-2">
                                <button onClick={() => setEditing(sector.id)} className="border border-slate-300 p-2 hover:border-slate-900">
                                    <RiPencilLine size={14} />
                                </button>
                                <button onClick={() => destroy(sector.id)} className="border border-slate-300 p-2 text-orange-600 hover:border-orange-600">
                                    <RiDeleteBinLine size={14} />
                                </button>
                            </div>
                        </div>
                    ),
                )}
            </div>
        </AdminLayout>
    );
}
