import { Head, router, useForm } from '@inertiajs/react';
import { RiCloseLine, RiDeleteBinLine, RiPencilLine } from '@remixicon/react';
import { FormEvent, useState } from 'react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Button } from '@/components/Ui/Button';
import { Checkbox } from '@/components/Ui/Checkbox';
import { FieldError } from '@/components/Ui/FieldError';
import { Input } from '@/components/Ui/Input';
import { Label } from '@/components/Ui/Label';

type Stage = { id: number; key: string; name: string; description: string | null; is_active: boolean; order: number; projects_count: number };

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
        <form onSubmit={submit} className="flex flex-wrap items-end gap-3 border border-slate-200 bg-slate-50 p-4">
            {!stage && (
                <div>
                    <Label className="text-xs">Clé</Label>
                    <Input size="sm" className="w-36" value={data.key} onChange={(e) => setData('key', e.target.value)} placeholder="early_stage" />
                    <FieldError>{errors.key}</FieldError>
                </div>
            )}
            <div>
                <Label className="text-xs">Nom</Label>
                <Input size="sm" className="w-48" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                <FieldError>{errors.name}</FieldError>
            </div>
            <div className="flex-1">
                <Label className="text-xs">Description</Label>
                <Input size="sm" value={data.description} onChange={(e) => setData('description', e.target.value)} />
            </div>
            {stage && (
                <Checkbox size="sm" label="Actif" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} />
            )}
            <Button type="submit" disabled={processing} size="sm" className="px-4">
                {stage ? 'Enregistrer' : 'Ajouter'}
            </Button>
            {stage && (
                <button type="button" onClick={onDone} className="px-2 py-1.5 text-caption" aria-label="Annuler la modification">
                    <RiCloseLine size={16} />
                </button>
            )}
        </form>
    );
}

export default function Index({ stages }: { stages: Stage[] }) {
    const [editing, setEditing] = useState<number | null>(null);

    function destroy(id: number) {
        if (confirm('Supprimer ce stade ?')) {
            router.delete(`/admin/stages/${id}`);
        }
    }

    return (
        <AdminLayout title="Stades">
            <Head title="Admin — Stades" />

            <div className="mb-6">
                <StageForm onDone={() => {}} />
            </div>

            <div className="border border-slate-200">
                {stages.map((stage) =>
                    editing === stage.id ? (
                        <div key={stage.id} className="border-b border-slate-200 last:border-b-0">
                            <StageForm stage={stage} onDone={() => setEditing(null)} />
                        </div>
                    ) : (
                        <div key={stage.id} className="flex items-center justify-between border-b border-slate-200 p-4 last:border-b-0">
                            <div>
                                <div className="flex items-center gap-2">
                                    <span className="text-item-title">{stage.name}</span>
                                    <span className="font-mono text-[10px] text-slate-400">{stage.key}</span>
                                    {!stage.is_active && <span className="font-mono text-[10px] uppercase text-slate-400">Inactif</span>}
                                </div>
                                <div className="text-caption">{stage.projects_count} projet(s) · {stage.description}</div>
                            </div>
                            <div className="flex gap-2">
                                <button onClick={() => setEditing(stage.id)} className="border border-slate-300 p-2 hover:border-slate-900">
                                    <RiPencilLine size={14} />
                                </button>
                                <button onClick={() => destroy(stage.id)} className="border border-slate-300 p-2 text-orange-600 hover:border-orange-600">
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
