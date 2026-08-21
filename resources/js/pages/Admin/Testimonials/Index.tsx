import { Head, router, useForm } from '@inertiajs/react';
import { RiCloseLine, RiDeleteBinLine, RiPencilLine } from '@remixicon/react';
import { FormEvent, useState } from 'react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Button } from '@/components/Ui/Button';
import { Checkbox } from '@/components/Ui/Checkbox';
import { FieldError } from '@/components/Ui/FieldError';
import { Input } from '@/components/Ui/Input';
import { Label } from '@/components/Ui/Label';
import { Textarea } from '@/components/Ui/Textarea';

type Testimonial = { id: number; name: string; role: string | null; company: string | null; content: string; is_published: boolean };

function TestimonialForm({ testimonial, onDone }: { testimonial?: Testimonial; onDone: () => void }) {
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
        <form onSubmit={submit} className="flex flex-col gap-3 border border-slate-200 bg-slate-50 p-4">
            <div className="flex flex-wrap gap-3">
                <div>
                    <Label className="text-xs">Nom</Label>
                    <Input size="sm" className="w-48" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                    <FieldError>{errors.name}</FieldError>
                </div>
                <div>
                    <Label className="text-xs">Rôle</Label>
                    <Input size="sm" className="w-40" value={data.role} onChange={(e) => setData('role', e.target.value)} />
                </div>
                <div>
                    <Label className="text-xs">Société</Label>
                    <Input size="sm" className="w-40" value={data.company} onChange={(e) => setData('company', e.target.value)} />
                </div>
            </div>
            <div>
                <Label className="text-xs">Témoignage (texte réel du contributeur)</Label>
                <Textarea size="sm" rows={3} value={data.content} onChange={(e) => setData('content', e.target.value)} />
                <FieldError>{errors.content}</FieldError>
            </div>
            <div className="flex items-center gap-3">
                <Checkbox size="sm" label="Publié" checked={data.is_published} onChange={(e) => setData('is_published', e.target.checked)} />
                <Button type="submit" disabled={processing} size="sm" className="px-4">
                    {testimonial ? 'Enregistrer' : 'Ajouter'}
                </Button>
                {testimonial && (
                    <button type="button" onClick={onDone} className="px-2 py-1.5 text-caption" aria-label="Annuler la modification"><RiCloseLine size={16} /></button>
                )}
            </div>
        </form>
    );
}

export default function Index({ testimonials }: { testimonials: Testimonial[] }) {
    const [editing, setEditing] = useState<number | null>(null);

    function destroy(id: number) {
        if (confirm('Supprimer ce témoignage ?')) router.delete(`/admin/testimonials/${id}`);
    }

    return (
        <AdminLayout title="Témoignages">
            <Head title="Admin — Témoignages" />
            <p className="mb-6 border border-orange-200 bg-orange-50 px-4 py-3 text-xs text-orange-700">
                N'entrez ici que les mots réels d'un utilisateur réel, avec son accord. Rien ici ne doit être
                inventé (spec §69).
            </p>

            <div className="mb-6"><TestimonialForm onDone={() => {}} /></div>

            <div className="border border-slate-200">
                {testimonials.map((t) =>
                    editing === t.id ? (
                        <div key={t.id} className="border-b border-slate-200 last:border-b-0">
                            <TestimonialForm testimonial={t} onDone={() => setEditing(null)} />
                        </div>
                    ) : (
                        <div key={t.id} className="flex items-start justify-between border-b border-slate-200 p-4 last:border-b-0">
                            <div>
                                <div className="flex items-center gap-2">
                                    <span className="text-item-title">{t.name}</span>
                                    <span className={`font-mono text-[10px] uppercase ${t.is_published ? 'text-blue-600' : 'text-slate-400'}`}>
                                        {t.is_published ? 'Publié' : 'Non publié'}
                                    </span>
                                </div>
                                <div className="mb-1 text-caption">{[t.role, t.company].filter(Boolean).join(', ')}</div>
                                <p className="max-w-lg text-sm text-slate-600">"{t.content}"</p>
                            </div>
                            <div className="flex gap-2">
                                <button onClick={() => setEditing(t.id)} className="border border-slate-300 p-2 hover:border-slate-900"><RiPencilLine size={14} /></button>
                                <button onClick={() => destroy(t.id)} className="border border-slate-300 p-2 text-orange-600 hover:border-orange-600"><RiDeleteBinLine size={14} /></button>
                            </div>
                        </div>
                    ),
                )}
            </div>
        </AdminLayout>
    );
}
