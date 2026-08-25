import { Head, router } from '@inertiajs/react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { Badge } from '@/components/Ui/Badge';

type ProjectRow = {
    id: number;
    slug: string;
    title: string;
    entrepreneurName: string;
    status: string;
    statusLabel: string;
    sectors: string[];
    fundingTargetMinor: number | null;
    fundingCurrency: string;
    createdAt: string;
};
type Paginated<T> = { data: T[]; links: { url: string | null; label: string; active: boolean }[] };

const statuses = ['draft', 'published', 'archived', 'closed'];

function formatTarget(minor: number | null, currency: string) {
    if (minor === null) return '—';
    return `${(minor / 100).toLocaleString('fr-FR')} ${currency}`;
}

export default function Index({ projects, filters }: { projects: Paginated<ProjectRow>; filters: { status?: string } }) {
    return (
        <AdminLayout title="Projets">
            <Head title="Admin — Projets" />

            <p className="mb-6 border border-slate-200 bg-slate-50 px-4 py-3 text-caption">
                Vue en lecture seule sur tous les projets de la plateforme. La gestion d'un projet reste réservée à
                son entrepreneur.
            </p>

            <div className="mb-4 flex gap-2">
                <button
                    onClick={() => router.get('/admin/projects')}
                    className={`border px-3 py-1.5 text-base font-semibold ${!filters.status ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-400 text-slate-700'}`}
                >
                    Tous
                </button>
                {statuses.map((s) => (
                    <button
                        key={s}
                        onClick={() => router.get('/admin/projects', { status: s })}
                        className={`border px-3 py-1.5 text-base font-semibold capitalize ${filters.status === s ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-400 text-slate-700'}`}
                    >
                        {s}
                    </button>
                ))}
            </div>

            <div className="border border-slate-300">
                {projects.data.length === 0 && <p className="p-6 text-center text-caption">Aucun projet.</p>}
                {projects.data.map((p) => (
                    <a
                        key={p.id}
                        href={`/projects/${p.slug}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center justify-between border-b border-slate-300 p-4 last:border-b-0 hover:bg-slate-50"
                    >
                        <div>
                            <div className="text-item-title">{p.title}</div>
                            <div className="text-caption">
                                Par {p.entrepreneurName} · {p.sectors.join(', ') || 'Aucun secteur'}
                            </div>
                        </div>
                        <div className="flex items-center gap-3">
                            <span className="font-mono text-[11px] text-slate-500">{formatTarget(p.fundingTargetMinor, p.fundingCurrency)}</span>
                            <Badge variant="outline">{p.statusLabel}</Badge>
                            <span className="font-mono text-[11px] text-slate-400">{new Date(p.createdAt).toLocaleDateString('fr-FR')}</span>
                        </div>
                    </a>
                ))}
            </div>

            {projects.links.length > 3 && (
                <div className="mt-6 flex flex-wrap justify-center gap-1">
                    {projects.links.map((link, i) => (
                        <button
                            key={i}
                            disabled={!link.url}
                            onClick={() => link.url && router.get(link.url)}
                            className={`min-w-[36px] border px-3 py-2 font-mono text-xs ${link.active ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-400 text-slate-700 disabled:opacity-30'}`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </AdminLayout>
    );
}
