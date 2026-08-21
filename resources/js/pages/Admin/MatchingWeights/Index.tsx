import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Button } from '@/components/Ui/Button';

type Weight = { criterion: string; label: string; weight: number };

export default function Index({ weights }: { weights: Weight[] }) {
    const { data, setData, put, transform, processing, errors, recentlySuccessful } = useForm({
        weights: weights.map((w) => ({ criterion: w.criterion, weight: Math.round(w.weight * 100) })),
    });

    const total = data.weights.reduce((sum, w) => sum + Number(w.weight || 0), 0);

    function setWeight(criterion: string, value: number) {
        setData('weights', data.weights.map((w) => (w.criterion === criterion ? { ...w, weight: value } : w)));
    }

    function submit(e: FormEvent) {
        e.preventDefault();
        // Inertia's `transform` is a method called before submitting,
        // not an option passed to put()/post() — a real API misuse tsc
        // caught once actual @inertiajs/react types were installed.
        transform((d) => ({ weights: d.weights.map((w) => ({ criterion: w.criterion, weight: w.weight / 100 })) }));
        put('/admin/matching-weights');
    }

    return (
        <AdminLayout title="Pondérations du matching">
            <Head title="Admin — Pondérations" />

            <p className="mb-6 border border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-700">
                Ces pourcentages pilotent directement le calcul du score de compatibilité (spec §23). Ils
                doivent totaliser 100%. Les changements s'appliquent aux prochains calculs de matching, pas
                rétroactivement aux scores déjà stockés.
            </p>

            {recentlySuccessful && (
                <div className="mb-6 border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">Pondérations mises à jour.</div>
            )}
            {errors.weights && <div className="mb-6 border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-700">{errors.weights}</div>}

            <form onSubmit={submit} className="border border-slate-200">
                {weights.map((w) => {
                    const current = data.weights.find((x) => x.criterion === w.criterion)!;
                    return (
                        <div key={w.criterion} className="flex items-center gap-4 border-b border-slate-200 p-4 last:border-b-0">
                            <div className="w-40 text-sm font-medium text-slate-900">{w.label}</div>
                            <input
                                type="range"
                                min={0}
                                max={100}
                                value={current.weight}
                                onChange={(e) => setWeight(w.criterion, Number(e.target.value))}
                                className="flex-1"
                            />
                            <div className="w-16 text-right font-mono text-sm font-bold text-slate-900">{current.weight}%</div>
                        </div>
                    );
                })}
                <div className={`flex items-center justify-between p-4 font-mono text-sm ${total === 100 ? 'text-blue-700' : 'text-orange-600'}`}>
                    <span>Total</span>
                    <span className="font-bold">{total}%</span>
                </div>
                <div className="p-4">
                    <Button
                        type="submit"
                        disabled={processing || total !== 100}
                        className="px-6 py-2.5 disabled:opacity-40"
                    >
                        {processing ? 'Enregistrement...' : 'Enregistrer'}
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
