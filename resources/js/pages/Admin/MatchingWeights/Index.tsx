import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Button } from '@/components/ui/button';

type Weight = { criterion: string; label: string; weight: number };

export default function Index({ weights }: { weights: Weight[] }) {
    const {
        data,
        setData,
        put,
        transform,
        processing,
        errors,
        recentlySuccessful,
    } = useForm({
        weights: weights.map((w) => ({
            criterion: w.criterion,
            weight: Math.round(w.weight * 100),
        })),
    });

    const total = data.weights.reduce(
        (sum, w) => sum + Number(w.weight || 0),
        0,
    );

    function setWeight(criterion: string, value: number) {
        setData(
            'weights',
            data.weights.map((w) =>
                w.criterion === criterion ? { ...w, weight: value } : w,
            ),
        );
    }

    function submit(e: FormEvent) {
        e.preventDefault();
        // Inertia's `transform` is a method called before submitting,
        // not an option passed to put()/post() — a real API misuse tsc
        // caught once actual @inertiajs/react types were installed.
        transform((d) => ({
            weights: d.weights.map((w) => ({
                criterion: w.criterion,
                weight: w.weight / 100,
            })),
        }));
        put('/admin/matching-weights');
    }

    return (
        <AdminLayout title="Pondérations du matching">
            <Head title="Admin — Pondérations" />

            <p className="mb-6 border border-rule bg-surface-2 px-4 py-3 text-caption">
                Ces pourcentages pilotent directement le calcul du score de
                compatibilité. Ils doivent totaliser 100%. Les changements
                s'appliquent aux prochains calculs de matching, pas
                rétroactivement aux scores déjà stockés.
            </p>

            {recentlySuccessful && (
                <div className="mb-6 border border-rule-strong bg-surface-2 px-4 py-3 text-lg leading-5 text-ink-muted">
                    Pondérations mises à jour.
                </div>
            )}
            {errors.weights && (
                <div className="mb-6 border border-rule-strong bg-surface-2 px-4 py-3 text-lg leading-5 text-ink-muted">
                    {errors.weights}
                </div>
            )}

            <form onSubmit={submit} className="border border-rule">
                {weights.map((w) => {
                    const current = data.weights.find(
                        (x) => x.criterion === w.criterion,
                    )!;
                    return (
                        <div
                            key={w.criterion}
                            className="flex items-center gap-4 border-b border-rule p-4 last:border-b-0"
                        >
                            <div className="w-40 text-lg font-medium text-ink">
                                {w.label}
                            </div>
                            <input
                                type="range"
                                min={0}
                                max={100}
                                value={current.weight}
                                onChange={(e) =>
                                    setWeight(
                                        w.criterion,
                                        Number(e.target.value),
                                    )
                                }
                                className="flex-1"
                            />
                            <div className="w-16 text-right font-mono text-lg font-bold text-ink">
                                {current.weight}%
                            </div>
                        </div>
                    );
                })}
                <div
                    className={`flex items-center justify-between p-4 font-mono text-lg ${total === 100 ? 'text-action' : 'text-action'}`}
                >
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
